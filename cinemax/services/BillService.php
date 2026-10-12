<?php

require_once __DIR__ . '/../models/Bill.php';
require_once __DIR__ . '/../services/TicketService.php';
require_once __DIR__ . '/../models/SeatHold.php';

class BillService
{
    private $billModel;
    private $ticketService;


    public function __construct($billModel, $ticketService)
    {
        $this->billModel = $billModel;
        $this->ticketService = $ticketService;
        (new SeatHold($this->billModel->getConnection()))->expire();
    }

    /**
     * Lấy tất cả bills
     */
    public function getAllBills()
    {
        return $this->billModel->getAllBills();
    }

    /**
     * Lấy bill theo ID
     */
    public function getBillById($billId)
    {
        return $this->billModel->getBillById($billId);
    }

    /**
     * Lấy bills phân trang với filter
     */
    public function getPaginated($page = 1, $limit = 10, $status = null, $search = null)
    {
        $total = $this->billModel->getTotalCount($status, $search);
        $totalPages = max(1, ceil($total / $limit));
        $page = max(1, min($page, $totalPages));
        $data = $this->billModel->getPaginated($page, $limit, $status, $search);

        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => $totalPages
        ];
    }

    /**
     * Lấy thống kê số lượng theo status
     */
    public function getStats()
    {
        return $this->billModel->getCountByStatus();
    }

    /**
     * Cập nhật trạng thái bill (xác nhận thanh toán)
     */
    public function confirmPayment($billId)
    {
        $bill = $this->billModel->getBillById($billId);
        if (!$bill) {
            throw new InvalidArgumentException("Đơn hàng không tồn tại.");
        }

        if ($bill['status'] !== 'pending') {
            throw new InvalidArgumentException("Chỉ có thể xác nhận đơn hàng đang chờ thanh toán.");
        }

        $conn=$this->billModel->getConnection();
        (new SeatHold($conn))->expire();
        $conn->begin_transaction();
        try {
            $userId=(int)$bill['user_id'];
            $lock=$conn->prepare('SELECT user_id FROM users WHERE user_id=? FOR UPDATE'); $lock->bind_param('i',$userId); $lock->execute(); $lock->get_result()->free();
            $stmt=$conn->prepare("UPDATE bills SET status='paid',paid_at=NOW() WHERE bill_id=? AND status='pending' AND (payment_expires_at IS NULL OR payment_expires_at>NOW())");
            $stmt->bind_param('i',$billId); $stmt->execute();
            if ($stmt->affected_rows!==1) throw new RuntimeException('Đơn đã hết thời gian thanh toán hoặc đã được xử lý.');
            $this->ticketService->updateStatusByBillId($billId,'paid');
            $stmt=$conn->prepare('DELETE FROM seat_holds WHERE bill_id=?'); $stmt->bind_param('i',$billId); $stmt->execute();
            $conn->commit(); return true;
        } catch (Throwable $e) { $conn->rollback(); throw $e; }
    }

    /**
     * Hủy đơn hàng
     */
    public function cancelBill($billId)
    {
        $bill = $this->billModel->getBillById($billId);
        if (!$bill) {
            throw new InvalidArgumentException("Đơn hàng không tồn tại.");
        }

        if ($bill['status'] === 'cancelled') {
            throw new InvalidArgumentException("Đơn hàng đã bị hủy trước đó.");
        }

        if ($bill['status'] === 'paid') {
            throw new InvalidArgumentException("Không thể hủy đơn hàng đã thanh toán. Vui lòng hoàn tiền.");
        }

        $conn=$this->billModel->getConnection(); $conn->begin_transaction();
        try {
            $stmt=$conn->prepare("UPDATE bills SET status='cancelled' WHERE bill_id=? AND status='pending'");
            $stmt->bind_param('i',$billId); $stmt->execute();
            if ($stmt->affected_rows!==1) throw new RuntimeException('Đơn đã được xử lý, không thể hủy.');
            $stmt=$conn->prepare("UPDATE tickets SET status='available',bill_id=NULL WHERE bill_id=? AND status='booked'");
            $stmt->bind_param('i',$billId); $stmt->execute();
            $stmt=$conn->prepare('DELETE FROM seat_holds WHERE bill_id=?'); $stmt->bind_param('i',$billId); $stmt->execute();
            $conn->commit(); return true;
        } catch (Throwable $e) { $conn->rollback(); throw $e; }
    }

    /**
     * Hoàn tiền đơn hàng
     */
    public function refundBill($billId)
    {
        $bill = $this->billModel->getBillById($billId);
        if (!$bill) {
            throw new InvalidArgumentException("Đơn hàng không tồn tại.");
        }

        if ($bill['status'] !== 'paid') {
            throw new InvalidArgumentException("Chỉ có thể hoàn tiền đơn hàng đã thanh toán.");
        }

        return $this->billModel->updateStatus($billId, 'refunded');
    }

    /**
     * Lấy chi tiết đơn hàng (tickets + combos)
     */
    public function getBillDetails($billId)
    {
        $bill = $this->billModel->getBillById($billId);
        if (!$bill) {
            return null;
        }

        $bill['tickets'] = $this->billModel->getTicketsByBillId($billId);
        $bill['combos'] = $this->billModel->getCombosByBillId($billId);

        return $bill;
    }

    public function saveBillCombos($billId, $combos)
    {
        if (empty($combos)) return;

        $this->billModel->insertBillCombos($billId, $combos);
    }

    public function getBillsByUserId($userId)
    {
        if (empty($userId)) {
            throw new InvalidArgumentException("User không tồn tại.");
        }
        return $this->billModel->getBillsByUserId($userId);
    }
}
