<?php
require_once __DIR__.'/../models/SeatHold.php';
require_once __DIR__.'/SeatSelectionRules.php';

class CheckoutService
{
    private int $holdSeconds;
    public function __construct(private mysqli $conn, private PromotionService $promotions)
    {
        $this->holdSeconds=(require __DIR__.'/../config/bookingConfig.php')['hold_seconds'];
    }

    public function expire(): void { (new SeatHold($this->conn))->expire(); }

    private function lockUser(int $userId): void
    {
        $stmt=$this->conn->prepare('SELECT user_id FROM users WHERE user_id=? AND status=1 FOR UPDATE');
        $stmt->bind_param('i',$userId); $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) throw new RuntimeException('Vui lòng đăng nhập bằng tài khoản đang hoạt động.');
    }

    private function validateSeatGaps(int $userId, int $showId, array $ids): void
    {
        $stmt=$this->conn->prepare("SELECT t.ticket_id,s.row_name,s.seat_number,
            (s.status=1 AND t.status='available' AND (h.ticket_id IS NULL OR (h.user_id=? AND h.bill_id IS NULL))) AS available
            FROM tickets t JOIN seats s ON s.seat_id=t.seat_id
            LEFT JOIN seat_holds h ON h.ticket_id=t.ticket_id AND h.expires_at>NOW()
            WHERE t.show_id=?");
        $stmt->bind_param('ii',$userId,$showId); $stmt->execute();
        $isolated=SeatSelectionRules::isolatedSeats($stmt->get_result()->fetch_all(MYSQLI_ASSOC),$ids);
        if ($isolated) throw new RuntimeException('Không được để trống 1 ghế lẻ bên cạnh ghế đã chọn ('.implode(', ',$isolated).'). Vui lòng chọn thêm ghế hoặc đổi vị trí; khoảng trống còn lại phải có ít nhất 2 ghế liền nhau.');
    }

    public function reserve(int $userId, int $showId, array $ids, ?string $existingToken=null): array
    {
        $ids=array_values(array_unique(array_map('intval',$ids))); sort($ids);
        if (!$ids || count($ids)>8 || min($ids)<=0) throw new RuntimeException('Vui lòng chọn từ 1 đến 8 ghế hợp lệ.');
        $this->expire();
        if ($existingToken) {
            $existing=$this->state($userId,$existingToken);
            $existingIds=array_column($existing['tickets']??[],'ticket_id'); sort($existingIds);
            if (($existing['active']??false) && $existingIds===$ids && (int)$existing['show_id']===$showId) return $existing;
        }
        $this->conn->begin_transaction();
        try {
            $this->lockUser($userId);
            $show=$this->conn->prepare('SELECT sh.show_id FROM shows sh JOIN movies m ON m.movie_id=sh.movie_id JOIN halls h ON h.hall_id=sh.hall_id JOIN cinemas c ON c.cinema_id=h.cinema_id WHERE sh.show_id=? AND m.status IN (1,2) AND sh.status=1 AND h.status=1 AND c.status=1 AND TIMESTAMP(sh.show_date,sh.start_time)>NOW() FOR UPDATE');
            $show->bind_param('i',$showId); $show->execute();
            if (!$show->get_result()->fetch_assoc()) throw new RuntimeException('Suất chiếu chưa mở bán, đã bắt đầu hoặc rạp/phòng đang đóng.');
            $ticket=$this->conn->prepare('SELECT t.ticket_id,t.status,sh.user_id AS hold_user,sh.bill_id AS hold_bill,sh.expires_at FROM tickets t JOIN seats s ON s.seat_id=t.seat_id LEFT JOIN seat_holds sh ON sh.ticket_id=t.ticket_id AND sh.expires_at>NOW() WHERE t.ticket_id=? AND t.show_id=? AND s.status=1 FOR UPDATE');
            $clearExpired=$this->conn->prepare('DELETE FROM seat_holds WHERE ticket_id=? AND expires_at<=NOW()');
            foreach ($ids as $id) {
                $ticket->bind_param('ii',$id,$showId); $ticket->execute(); $row=$ticket->get_result()->fetch_assoc();
                if (!$row || $row['status']!=='available' || ($row['hold_user']!==null && (int)$row['hold_user']!==$userId)) throw new RuntimeException('Một ghế đã được người khác giữ hoặc đặt. Vui lòng chọn lại.');
                $clearExpired->bind_param('i',$id); $clearExpired->execute();
            }
            $delete=$this->conn->prepare('DELETE FROM seat_holds WHERE user_id=? AND bill_id IS NULL');
            $delete->bind_param('i',$userId); $delete->execute();
            $this->validateSeatGaps($userId,$showId,$ids);
            $token=bin2hex(random_bytes(32)); $seconds=$this->holdSeconds;
            $insert=$this->conn->prepare('INSERT INTO seat_holds(ticket_id,user_id,hold_token,expires_at) VALUES(?,?,?,DATE_ADD(NOW(),INTERVAL ? SECOND))');
            foreach ($ids as $id) { $insert->bind_param('iisi',$id,$userId,$token,$seconds); $insert->execute(); }
            $this->conn->commit();
            return $this->state($userId,$token);
        } catch (Throwable $e) { $this->conn->rollback(); throw $e; }
    }

    public function cancel(int $userId, string $token): void
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) throw new RuntimeException('Phiên giữ ghế không hợp lệ.');
        $this->conn->begin_transaction();
        try {
            // Serialize with payment submission; only release unsubmitted holds.
            $this->lockUser($userId);
            $stmt=$this->conn->prepare('SELECT bill_id FROM seat_holds WHERE user_id=? AND hold_token=? FOR UPDATE');
            $stmt->bind_param('is',$userId,$token); $stmt->execute();
            foreach ($stmt->get_result() as $row) {
                if ($row['bill_id']!==null) throw new RuntimeException('Đơn hàng đã được gửi thanh toán, không thể hủy phiên giữ ghế này.');
            }
            $delete=$this->conn->prepare('DELETE FROM seat_holds WHERE user_id=? AND hold_token=? AND bill_id IS NULL');
            $delete->bind_param('is',$userId,$token); $delete->execute();
            $this->conn->commit();
        } catch (Throwable $e) { $this->conn->rollback(); throw $e; }
    }

    public function state(int $userId, string $token, bool $lock=false): array
    {
        if (!preg_match('/^[a-f0-9]{64}$/',$token)) return ['active'=>false];
        $sql="SELECT t.ticket_id,t.show_id,t.price,t.status,h.bill_id,h.expires_at,
                     GREATEST(0,TIMESTAMPDIFF(SECOND,NOW(),h.expires_at)) AS remaining_seconds,
                     CONCAT(s.row_name,s.seat_number) AS seat_name
              FROM seat_holds h JOIN tickets t ON t.ticket_id=h.ticket_id JOIN seats s ON s.seat_id=t.seat_id
              WHERE h.user_id=? AND h.hold_token=? AND h.expires_at>NOW() AND h.bill_id IS NULL AND t.status='available'
              ORDER BY t.ticket_id".($lock?' FOR UPDATE':'');
        $stmt=$this->conn->prepare($sql); $stmt->bind_param('is',$userId,$token); $stmt->execute();
        $rows=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        if (!$rows) return ['active'=>false];
        return ['active'=>true,'token'=>$token,'show_id'=>(int)$rows[0]['show_id'],'remaining_seconds'=>(int)$rows[0]['remaining_seconds'],'expires_at'=>$rows[0]['expires_at'],'tickets'=>$rows,'seat_total'=>array_sum(array_column($rows,'price'))];
    }

    public function comboSummary(array $combos, bool $lock=false): array
    {
        $quantities=[];
        foreach ($combos as $combo) {
            $id=(int)($combo['combo_id']??0); $qty=(int)($combo['quantity']??0);
            if ($id<=0 || $qty<=0 || $qty>20) throw new RuntimeException('Số lượng combo không hợp lệ.');
            $quantities[$id]=($quantities[$id]??0)+$qty;
            if ($quantities[$id]>20) throw new RuntimeException('Mỗi combo tối đa 20 phần.');
        }
        ksort($quantities); $rows=[]; $total=0;
        $stmt=$this->conn->prepare('SELECT combo_id,name,price FROM combos WHERE combo_id=? AND status=1'.($lock?' FOR UPDATE':''));
        foreach ($quantities as $id=>$quantity) {
            $stmt->bind_param('i',$id); $stmt->execute(); $combo=$stmt->get_result()->fetch_assoc();
            if (!$combo) throw new RuntimeException('Combo không còn được bán.');
            $combo['quantity']=$quantity; $rows[]=$combo; $total+=(float)$combo['price']*$quantity;
        }
        return ['combos'=>$rows,'total'=>$total];
    }

    public function submit(int $userId,string $token,array $ids,array $combos,string $code): int
    {
        $this->expire();
        $this->conn->begin_transaction();
        try {
            $this->lockUser($userId);
            $hold=$this->state($userId,$token,true);
            $ids=array_values(array_unique(array_map('intval',$ids))); sort($ids);
            if (!$hold['active'] || array_column($hold['tickets'],'ticket_id')!==$ids || $hold['remaining_seconds']<=0) throw new RuntimeException('Phiên giữ ghế đã hết hạn. Vui lòng chọn ghế lại.');
            $showId=$hold['show_id'];
            $this->validateSeatGaps($userId,$showId,$ids);
            $show=$this->conn->prepare('SELECT sh.show_id FROM shows sh JOIN movies m ON m.movie_id=sh.movie_id JOIN halls h ON h.hall_id=sh.hall_id JOIN cinemas c ON c.cinema_id=h.cinema_id WHERE sh.show_id=? AND m.status IN (1,2) AND sh.status=1 AND h.status=1 AND c.status=1 AND TIMESTAMP(sh.show_date,sh.start_time)>NOW() FOR UPDATE');
            $show->bind_param('i',$showId); $show->execute();
            if (!$show->get_result()->fetch_assoc()) throw new RuntimeException('Suất chiếu không còn nhận đặt vé.');
            $food=$this->comboSummary($combos,true);
            $total=(float)$hold['seat_total']+$food['total']; $discount=0; $final=$total; $promoId=null;
            if ($code!=='') { $promo=$this->promotions->applyPromotionCode($code,$total,$userId); $promoId=$promo['promotion_id']; $discount=$promo['discount_amount']; $final=$promo['final_amount']; }
            $count=count($ids); $expiry=$hold['expires_at'];
            $stmt=$this->conn->prepare("INSERT INTO bills(user_id,total_tickets,total_amount,discount_amount,final_amount,promotion_id,status,payment_expires_at) VALUES(?,?,?,?,?,?,'pending',?)");
            $stmt->bind_param('iidddis',$userId,$count,$total,$discount,$final,$promoId,$expiry); $stmt->execute(); $billId=(int)$this->conn->insert_id;
            $book=$this->conn->prepare("UPDATE tickets t JOIN seat_holds h ON h.ticket_id=t.ticket_id SET t.status='booked',t.bill_id=? WHERE t.ticket_id=? AND t.status='available' AND h.hold_token=? AND h.user_id=? AND h.bill_id IS NULL AND h.expires_at>NOW()");
            foreach ($ids as $id) { $book->bind_param('iisi',$billId,$id,$token,$userId); $book->execute(); if ($book->affected_rows!==1) throw new RuntimeException('Phiên giữ ghế đã hết hạn hoặc ghế đã thay đổi.'); }
            $link=$this->conn->prepare('UPDATE seat_holds SET bill_id=? WHERE user_id=? AND hold_token=?'); $link->bind_param('iis',$billId,$userId,$token); $link->execute();
            $insertCombo=$this->conn->prepare('INSERT INTO bill_combos(bill_id,combo_id,quantity,price) VALUES(?,?,?,?)');
            foreach ($food['combos'] as $combo) { $insertCombo->bind_param('iiid',$billId,$combo['combo_id'],$combo['quantity'],$combo['price']); $insertCombo->execute(); }
            $this->conn->commit(); return $billId;
        } catch (Throwable $e) { $this->conn->rollback(); throw $e; }
    }
}
