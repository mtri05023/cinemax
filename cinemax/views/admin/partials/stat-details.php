<?php
require_once __DIR__.'/../../../services/DashboardService.php';
$escape=fn($value)=>htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');
$titles=['revenue'=>'Chi tiết doanh thu trong kỳ','tickets'=>'Vé đã bán trong kỳ','members'=>'Thành viên mới trong kỳ','orders'=>'Đơn đã thanh toán trong kỳ'];
try {
    $metric=is_string($_GET['metric']??null)?$_GET['metric']:'';
    if (!isset($titles[$metric]) || (($page==='users')!==($metric==='members'))) throw new InvalidArgumentException('Mục thống kê không hợp lệ.');
    $period=DashboardService::period($_GET);
    $service=new DashboardService($conn);
    $details=$service->details($period,$metric,(int)($_GET['p']??1));
    $report=$service->report($period);
} catch (InvalidArgumentException $e) {
    echo '<div class="dashboard-content"><p role="alert">'.$escape($e->getMessage()).'</p><a href="index.php?page=dashboard">Về thống kê</a></div>'; return;
}
$periodParams=['period'=>$period['mode'],'date'=>$period['date'],'month'=>$period['month'],'from'=>$period['from'],'to'=>$period['to']];
$back='index.php?'.http_build_query(['page'=>'dashboard']+$periodParams);
$money=fn($value)=>number_format((float)$value,0,',','.').' ₫';
$headers=$metric==='members'?['ID','Họ tên','Email','SĐT','Ngày tham gia']:($metric==='tickets'?['Mã vé','Mã đơn','Khách hàng','Phim','Rạp','Phòng','Ghế','Giá vé','Thanh toán lúc']:['Mã đơn','Khách hàng','Số vé','Đã thanh toán','Thanh toán lúc']);
?>
<section class="<?= $metric==='members'?'users':'bookings' ?>">
    <header class="admin-header"><h1><?= $escape($titles[$metric]) ?></h1><div class="header-actions"><a class="btn-action" href="<?= $escape($back) ?>">Về thống kê</a><a class="btn-action" href="index.php?page=<?= $escape($page) ?>"><?= $metric==='members'?'Quản lý người dùng':'Quản lý đơn hàng' ?></a></div></header>
    <div class="dashboard-content">
        <div class="dashboard-card">
            <div class="card-header"><h3><?= $escape($period['label']) ?></h3><strong><?= $metric==='revenue'?$money($report['summary']['revenue']):($metric==='tickets'?(int)$report['summary']['tickets'].' vé':$details['total'].($metric==='members'?' thành viên':' đơn đã thanh toán')) ?></strong></div>
            <p class="dashboard-report-note"><?= $metric==='members'?'Lọc theo ngày tham gia của người dùng.':'Chỉ gồm đơn đã thanh toán trong kỳ, lọc theo thời điểm thanh toán. Doanh thu là số tiền thực thu sau giảm giá, gồm vé và combo; giá vé trong bảng là giá trước giảm giá.' ?></p>
            <div class="table-responsive"><table class="data-table" id="statDetailsTable"><thead><tr><?php foreach ($headers as $header): ?><th><?= $escape($header) ?></th><?php endforeach; ?></tr></thead><tbody>
                <?php foreach ($details['rows'] as $row):
                    $cells=$metric==='members'?['#'.$row['id'],$row['full_name'],$row['email'],$row['phone']??'-']:($metric==='tickets'?['#'.$row['id'],'#'.$row['bill_id'],$row['full_name'],$row['title']??'-',$row['cinema']??'-',$row['hall']??'-',$row['seat']??'-',$money($row['price'])]:['#'.$row['id'],$row['full_name'],$row['total_tickets'],$money($row['final_amount'])]);
                    $cells[]=date('H:i d/m/Y',strtotime($row['event_time'])); ?>
                    <tr><?php foreach ($cells as $cell): ?><td><?= $escape($cell) ?></td><?php endforeach; ?></tr>
                <?php endforeach; ?>
                <?php if (!$details['rows']): ?><tr><td colspan="<?= count($headers) ?>">Không có dữ liệu trong kỳ đã chọn.</td></tr><?php endif; ?>
            </tbody></table></div>
            <?php
            require_once __DIR__.'/../../components/pagination.php';
            echo renderPagination(['totalItems'=>$details['total'],'limit'=>10,'currentPage'=>$details['page']]);
            ?>
        </div>
    </div>
</section>
