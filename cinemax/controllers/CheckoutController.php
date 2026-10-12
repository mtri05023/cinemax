<?php
require_once __DIR__.'/../services/CheckoutService.php';

class CheckoutController
{
    public function __construct(private CheckoutService $checkout, private mysqli $conn) {}

    public function handle(string $page, ?string $action, ?array $user): ?array
    {
        $this->checkout->expire();
        $_SESSION['checkout_csrf'] ??= bin2hex(random_bytes(32));
        $uid=(int)($user['user_id']??0);
        if ($action==='checkout_state') {
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store');
            if (!$uid) { http_response_code(401); echo json_encode(['active'=>false]); exit; }
            $billId=(int)($_GET['bill_id']??0);
            if ($billId>0) {
                $stmt=$this->conn->prepare("SELECT status,GREATEST(0,TIMESTAMPDIFF(SECOND,NOW(),payment_expires_at)) AS remaining_seconds FROM bills WHERE bill_id=? AND user_id=?");
                $stmt->bind_param('ii',$billId,$uid); $stmt->execute(); $bill=$stmt->get_result()->fetch_assoc();
                $state=['active'=>$bill && $bill['status']==='pending','paid'=>$bill && $bill['status']==='paid','remaining_seconds'=>(int)($bill['remaining_seconds']??0)];
            } else $state=$this->checkout->state($uid,(string)($_GET['hold_token']??''));
            // Never disclose another customer's hold rows or token through polling.
            echo json_encode(['active'=>$state['active'],'paid'=>$state['paid']??false,'remaining_seconds'=>$state['remaining_seconds']??0]); exit;
        }
        if (!in_array($page,['food_selection','payment'],true)) return null;
        if (!$uid) { header('Location: /cinemax/cinemax/views/auth/login.php?error=required'); exit; }
        if ($action==='cancel_checkout' && $_SERVER['REQUEST_METHOD']!=='POST') {
            http_response_code(405); header('Allow: POST'); exit;
        }
        try {
            if ($_SERVER['REQUEST_METHOD']==='POST' && !hash_equals($_SESSION['checkout_csrf'],(string)($_POST['checkout_csrf']??''))) throw new RuntimeException('Phiên không hợp lệ. Vui lòng chọn ghế lại.');
            if ($action==='cancel_checkout') {
                $token=(string)($_POST['hold_token']??'');
                if (!hash_equals((string)($_SESSION['checkout_token']??''),$token) || $token==='') throw new RuntimeException('Phiên giữ ghế không hợp lệ.');
                $show=(int)($_SESSION['checkout_show_id']??0);
                $this->checkout->cancel($uid,$token);
                unset($_SESSION['checkout_token'],$_SESSION['checkout_combos'],$_SESSION['checkout_show_id'],$_SESSION['checkout_error']);
                header('Location: index.php?page='.($show>0?'seat_selection&show_id='.$show:'movies')); exit;
            }
            if ($action==='confirm_payment') {
                if ($_SERVER['REQUEST_METHOD']!=='POST') throw new RuntimeException('Thanh toán phải gửi bằng POST.');
                $token=(string)($_POST['hold_token']??'');
                $previous=$_SESSION['last_checkout']??null;
                if ($previous && $previous['token']===$token && $previous['user_id']===$uid) {
                    header('Location: index.php?page=booking_success&bill_id='.$previous['bill_id']); exit;
                }
                if (!in_array($_POST['payment_method']??'', ['ATM','MOMO','VISA'],true)) throw new RuntimeException('Phương thức thanh toán không hợp lệ.');
                $ids=is_array($_POST['ticket_ids']??null)?$_POST['ticket_ids']:[];
                $combos=is_array($_POST['combos']??null)?$_POST['combos']:[];
                $billId=$this->checkout->submit($uid,$token,$ids,$combos,strtoupper(trim((string)($_POST['promotion_code']??''))));
                $_SESSION['last_checkout']=['token'=>$token,'bill_id'=>$billId,'user_id'=>$uid];
                unset($_SESSION['checkout_token'],$_SESSION['checkout_combos']);
                header('Location: index.php?page=booking_success&bill_id='.$billId); exit;
            }
            if ($page==='food_selection' && $_SERVER['REQUEST_METHOD']==='POST') {
                $ids=is_array($_POST['ticket_ids']??null)?$_POST['ticket_ids']:[];
                $hold=$this->checkout->reserve($uid,(int)($_POST['showtime_id']??0),$ids,$_SESSION['checkout_token']??null);
                $_SESSION['checkout_token']=$hold['token']; $_SESSION['checkout_show_id']=$hold['show_id']; $_SESSION['checkout_combos']=[];
            } else {
                $token=(string)($_POST['hold_token']??$_SESSION['checkout_token']??'');
                $hold=$this->checkout->state($uid,$token);
                if (!$hold['active']) throw new RuntimeException('Phiên giữ ghế đã hết hạn. Vui lòng chọn ghế lại.');
                if ($page==='payment' && $_SERVER['REQUEST_METHOD']==='POST') {
                    $raw=$_POST['combos']??'[]';
                    $combos=is_array($raw)?$raw:json_decode((string)$raw,true,512,JSON_THROW_ON_ERROR);
                    if (!is_array($combos)) throw new RuntimeException('Combo không hợp lệ.');
                    $_SESSION['checkout_combos']=$this->checkout->comboSummary($combos)['combos'];
                }
            }
            $hold['food']=$this->checkout->comboSummary($_SESSION['checkout_combos']??[]);
            return $hold;
        } catch (Throwable $e) {
            $_SESSION['checkout_error']=$e->getMessage();
            $hold=$this->checkout->state($uid,(string)($_SESSION['checkout_token']??''));
            if ($page==='payment' && $hold['active'] && in_array($action,['confirm_payment','cancel_checkout'],true)) {
                header('Location: index.php?page=payment');
            } else {
                $show=(int)($_POST['showtime_id']??$_SESSION['checkout_show_id']??0);
                header('Location: index.php?page='.($show>0?'seat_selection&show_id='.$show:'movies').'&expired=1');
            }
            exit;
        }
    }
}
