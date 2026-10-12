<?php
if (empty($checkoutHold['active'])) return;
$timerData=['remaining_seconds'=>(int)$checkoutHold['remaining_seconds'],'hold_token'=>$checkoutHold['token']??'','bill_id'=>$checkoutHold['bill_id']??0,'show_id'=>$checkoutHold['show_id']??0];
?>
<div class="checkout-clock" role="status" aria-live="polite">
    <span>Thời gian giữ ghế</span><strong id="checkoutCountdown">--:--</strong>
    <small>Hết thời gian, ghế sẽ được trả lại.</small>
</div>
<script>window.checkoutTimer=<?= json_encode($timerData,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;</script>
<script src="/cinemax/cinemax/public/assets/js/checkout-timer.js?v=<?= filemtime(__DIR__.'/../../../public/assets/js/checkout-timer.js') ?>"></script>
<link rel="stylesheet" href="/cinemax/cinemax/public/assets/css/checkout.css?v=<?= filemtime(__DIR__.'/../../../public/assets/css/checkout.css') ?>">
