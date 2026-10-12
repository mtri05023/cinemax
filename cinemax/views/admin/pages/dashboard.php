<?php
require_once __DIR__.'/../../../services/DashboardService.php';
$reportError=null;
try { $reportPeriod=DashboardService::period($_GET); }
catch (InvalidArgumentException $e) { $reportError=$e->getMessage(); $reportPeriod=DashboardService::period([]); }
$report=(new DashboardService($conn))->report($reportPeriod);
$periodRevenue=(float)$report['summary']['revenue'];
$periodTickets=(int)$report['summary']['tickets'];
$periodNewUsers=$report['newUsers'];
$paidOrders=(int)$report['summary']['orders'];
$latestOrders=$report['orders'];
$topMovies=$report['movies'];
$topCinemas=$report['cinemas'];
$topHalls=$report['halls'];
$revenueLabels=$report['labels'];
$revenueData=$report['values'];
$detailLink=function(string $metric) use ($reportPeriod): string {
    return htmlspecialchars('index.php?'.http_build_query(['page'=>$metric==='members'?'users':'bookings','metric'=>$metric,'period'=>$reportPeriod['mode'],'date'=>$reportPeriod['date'],'month'=>$reportPeriod['month'],'from'=>$reportPeriod['from'],'to'=>$reportPeriod['to']]),ENT_QUOTES,'UTF-8');
};
if (!defined('CINEMAX_BASE_URL')) {
    define('CINEMAX_BASE_URL', '/cinemax/cinemax');
}

function getPosterUrl($poster)
{
    $placeholder = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='300' height='450' viewBox='0 0 300 450'><rect width='300' height='450' fill='%23111111'/><rect x='15' y='15' width='270' height='420' rx='12' fill='%231b1b1b' stroke='%23333333'/><text x='50%25' y='48%25' fill='%23999999' font-size='22' font-family='Arial, sans-serif' text-anchor='middle'>No Image</text><text x='50%25' y='55%25' fill='%23666666' font-size='14' font-family='Arial, sans-serif' text-anchor='middle'>Cinemax</text></svg>";
    $poster = trim(str_replace('\\', '/', (string)$poster));

    if ($poster === '') {
        return $placeholder;
    }

    if (preg_match('/^https?:\/\//i', $poster)) {
        return $poster;
    }

    $baseUrl = CINEMAX_BASE_URL;
    $projectRoot = realpath(__DIR__ . '/../../../');
    if ($projectRoot === false) {
        return $placeholder;
    }

    if (strpos($poster, $baseUrl . '/') === 0) {
        $webPath = $poster;
        $relativePath = ltrim(substr($poster, strlen($baseUrl)), '/');
    } elseif ($poster[0] === '/') {
        $webPath = $baseUrl . $poster;
        $relativePath = ltrim($poster, '/');
    } else {
        $relativePath = ltrim($poster, '/');
        if (strpos($relativePath, 'public/') === 0) {
            $webPath = $baseUrl . '/' . $relativePath;
        } elseif (strpos($relativePath, 'assets/') === 0) {
            $relativePath = 'public/' . $relativePath;
            $webPath = $baseUrl . '/' . $relativePath;
        } elseif (strpos($relativePath, 'uploads/') === 0) {
            $relativePath = 'public/assets/' . $relativePath;
            $webPath = $baseUrl . '/' . $relativePath;
        } else {
            $relativePath = 'public/assets/uploads/movies/' . $relativePath;
            $webPath = $baseUrl . '/' . $relativePath;
        }
    }

    $filePath = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, strtok($relativePath, '?'));
    if (!is_file($filePath)) {
        return $placeholder;
    }

    return $webPath;
}
?>

<section class="dashboard">
    <header class="admin-header">
        <h1>Thống kê</h1>
        <span class="dashboard-period-badge"><?= htmlspecialchars($reportPeriod['label']) ?></span>
    </header>

    <div class="dashboard-content">
        <?php if ($reportError): ?><p class="alert alert-error" role="alert"><?= htmlspecialchars($reportError) ?> Đang hiển thị tháng hiện tại.</p><?php endif; ?>
        <form method="GET" class="dashboard-card dashboard-filters" id="dashboardFilters">
            <input type="hidden" name="page" value="dashboard">
            <div class="dashboard-filter-field"><label for="statsPeriod">Thống kê theo</label><select id="statsPeriod" name="period"><option value="day" <?= $reportPeriod['mode']==='day'?'selected':'' ?>>Ngày</option><option value="month" <?= $reportPeriod['mode']==='month'?'selected':'' ?>>Tháng</option><option value="range" <?= $reportPeriod['mode']==='range'?'selected':'' ?>>Khoảng ngày</option></select></div>
            <div class="dashboard-filter-field" data-period="day"><label for="statsDate">Ngày</label><input type="date" id="statsDate" name="date" value="<?= htmlspecialchars($reportPeriod['date']) ?>"></div>
            <div class="dashboard-filter-field" data-period="month"><label for="statsMonth">Tháng</label><input type="month" id="statsMonth" name="month" value="<?= htmlspecialchars($reportPeriod['month']) ?>"></div>
            <div class="dashboard-filter-field" data-period="range"><label for="statsFrom">Từ ngày</label><input type="date" id="statsFrom" name="from" value="<?= htmlspecialchars($reportPeriod['from']) ?>"></div>
            <div class="dashboard-filter-field" data-period="range"><label for="statsTo">Đến ngày</label><input type="date" id="statsTo" name="to" value="<?= htmlspecialchars($reportPeriod['to']) ?>"></div>
            <button type="submit" class="btn-add">Xem thống kê</button><a href="index.php?page=dashboard" class="btn-action">Tháng hiện tại</a>
        </form>
        <p class="dashboard-report-note">Chỉ tính đơn đã thanh toán trong kỳ. Doanh thu rạp/phòng gồm vé và combo; doanh thu phim chỉ gồm vé, sau giảm giá. Phim đã ẩn và rạp đã đóng vẫn được giữ trong thống kê lịch sử.</p>

        <!-- STATS -->
        <div class="stats-grid">

            <a class="stat-card stat-card-link" href="<?= $detailLink('revenue') ?>">
                <div class="stat-icon" style="background: rgba(229, 9, 20, 0.1);">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="1" x2="12" y2="23"></line>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Doanh thu trong kỳ</span>
                    <span class="stat-value" style="color: #e50914;">
                        <?php echo number_format($periodRevenue, 0, ',', '.'); ?> ₫
                    </span>
                </div>
            </a>

            <a class="stat-card stat-card-link" href="<?= $detailLink('tickets') ?>">
                <div class="stat-icon" style="background: rgba(70, 211, 105, 0.1);">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                    </svg>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Vé đã bán trong kỳ</span>
                    <span class="stat-value" style="color: #46d369;">
                        <?php echo (int)$periodTickets; ?>
                    </span>
                </div>
            </a>

            <a class="stat-card stat-card-link" href="<?= $detailLink('members') ?>">
                <div class="stat-icon" style="background: rgba(255, 165, 0, 0.1);">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                    </svg>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Thành viên mới trong kỳ</span>
                    <span class="stat-value" style="color: #ffa500;">
                        <?php echo (int)$periodNewUsers; ?>
                    </span>
                </div>
            </a>

            <a class="stat-card stat-card-link" href="<?= $detailLink('orders') ?>">
                <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1);">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2">
                        <rect x="2" y="7" width="20" height="15" rx="2" ry="2"></rect>
                        <polyline points="17 2 12 7 7 2"></polyline>
                    </svg>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Đơn đã thanh toán</span>
                    <span class="stat-value" style="color: #3b82f6;">
                        <?php echo (int)$paidOrders; ?>
                    </span>
                </div>
            </a>

        </div>

        <div class="dashboard-rankings">
            <?php foreach (['cinemas'=>['title'=>'Top rạp có doanh thu cao nhất','rows'=>$topCinemas],'halls'=>['title'=>'Top phòng có doanh thu cao nhất','rows'=>$topHalls]] as $rankingKey=>$ranking): ?>
                <div class="dashboard-card" id="ranking-<?= $rankingKey ?>"><div class="card-header"><h3><?= $ranking['title'] ?></h3></div>
                    <?php if (!$ranking['rows']): ?><p class="dashboard-empty">Chưa có doanh thu trong kỳ đã chọn.</p><?php endif; ?>
                    <?php foreach ($ranking['rows'] as $index=>$item): ?>
                        <div class="dashboard-rank-item"><span class="dashboard-rank-number"><?= $index+1 ?></span><div class="dashboard-rank-info"><strong><?= htmlspecialchars($item['name']) ?></strong><?php if (isset($item['cinema_name'])): ?><small><?= htmlspecialchars($item['cinema_name']) ?></small><?php endif; ?><small><?= (int)$item['tickets'] ?> vé đã bán</small></div><strong class="dashboard-rank-revenue"><?= number_format((float)$item['revenue'],0,',','.') ?> ₫</strong></div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- TOP MOVIES + BIỂU ĐỒ & ĐƠN HÀNG MỚI -->
        <div class="dashboard-grid-2" style="margin-top: 30px;">

            <!-- Top phim + biểu đồ doanh thu tháng -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3>Doanh thu trong kỳ & Top phim</h3>
                </div>

                <p class="dashboard-chart-caption"><?= $reportPeriod['mode']==='day'?'Doanh thu theo giờ trong ngày':($reportPeriod['monthly']?'Doanh thu theo tháng':'Doanh thu theo ngày') ?> · VNĐ</p>
                <div class="dashboard-chart" role="region" aria-label="Biểu đồ doanh thu trong kỳ">
                    <?php $chartWidth=max(620,count($revenueLabels)*26+110); $chartMax=max(1,...$revenueData); $barStep=($chartWidth-110)/max(1,count($revenueLabels)); ?>
                    <svg class="dashboard-native-chart" viewBox="0 0 <?= $chartWidth ?> 235" role="img" aria-label="Doanh thu đã thanh toán trong <?= htmlspecialchars($reportPeriod['label']) ?>">
                        <?php for ($tick=0;$tick<=2;$tick++): $tickY=190-$tick*85; ?>
                            <line x1="90" y1="<?= $tickY ?>" x2="<?= $chartWidth-20 ?>" y2="<?= $tickY ?>" stroke="#404040" />
                            <text x="82" y="<?= $tickY+4 ?>" text-anchor="end" fill="#b3b3b3" font-size="10"><?= number_format($chartMax*$tick/2,0,',','.') ?> ₫</text>
                        <?php endfor; ?>
                        <?php foreach ($revenueData as $index=>$value): $barHeight=$value/$chartMax*170; $barX=90+$index*$barStep; ?>
                            <rect tabindex="0" x="<?= $barX+($barStep-min(36,$barStep*.65))/2 ?>" y="<?= 190-max(2,$barHeight) ?>" width="<?= min(36,$barStep*.65) ?>" height="<?= max(2,$barHeight) ?>" rx="3" fill="<?= $value>0?'#e50914':'#2f2f2f' ?>" aria-label="<?= htmlspecialchars($revenueLabels[$index].': '.number_format($value,0,',','.').' ₫') ?>"><title><?= htmlspecialchars($revenueLabels[$index].': '.number_format($value,0,',','.').' ₫') ?></title></rect>
                            <?php if ($index%max(1,(int)ceil(count($revenueData)/10))===0 || $index===count($revenueData)-1): ?><text x="<?= $barX+$barStep/2 ?>" y="212" text-anchor="middle" fill="#b3b3b3" font-size="10"><?= htmlspecialchars($revenueLabels[$index]) ?></text><?php endif; ?>
                        <?php endforeach; ?>
                    </svg>
                </div>
                <p class="dashboard-chart-readout" id="chartReadout" role="status" aria-live="polite">Di chuột hoặc chọn cột để xem số tiền.</p>

                <h4>Top phim theo doanh thu vé</h4>
                <div class="top-movies-list" id="ranking-movies">
                    <?php if (empty($topMovies)): ?>
                        <div style="text-align:center; padding: 16px; color:#888;">
                            Chưa có dữ liệu bán vé.
                        </div>
                    <?php else: ?>
                        <?php foreach ($topMovies as $movieIndex=>$movie): ?>
                            <div class="top-movie-item">
                                <span class="dashboard-rank-number"><?= $movieIndex+1 ?></span>
                                <img src="<?= htmlspecialchars(getPosterUrl($movie['poster_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="tm-poster" alt="Poster">
                                <div class="tm-info">
                                    <h4><?php echo htmlspecialchars($movie['Title'] ?? ''); ?></h4>
                                    <div class="tm-stats">
                                        <span class="tm-highlight">
                                            <?php echo (int)($movie['TicketsSold'] ?? 0); ?>
                                        </span> vé đã bán
                                        <br>
                                        Doanh thu vé:
                                        <span style="color: #fff;">
                                            <?php echo number_format((float)($movie['Revenue'] ?? 0), 0, ',', '.'); ?> ₫
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Đơn hàng mới nhất (đặt ở cột phải, dưới hơn một chút giống layout mẫu) -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3>Đơn đã thanh toán trong kỳ</h3>
                    <a href="index.php?page=bookings" class="card-action">Xem tất cả →</a>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Mã</th>
                                <th>Khách hàng</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                                <th>Thanh toán lúc</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($latestOrders)): ?>
                                <tr>
                                    <td colspan="5" style="text-align:center; padding:16px; color:#888;">
                                        Chưa có đơn hàng nào.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($latestOrders as $order): ?>
                                    <tr>
                                        <td>#<?php echo htmlspecialchars((string)$order['bill_id']); ?></td>
                                        <td><?php echo htmlspecialchars($order['full_name'] ?? ''); ?></td>
                                        <td style="color: var(--primary-color); font-weight: bold;">
                                            <?php echo number_format((float)$order['final_amount'], 0, ',', '.'); ?> ₫
                                        </td>
                                        <td>Đã thanh toán</td>
                                        <td style="color: #888; font-size: 13px;">
                                            <?php
                                            if (!empty($order['created_at'])) {
                                                $dt = new DateTime($order['created_at']);
                                                echo $dt->format('H:i d/m/Y');
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Biểu đồ cột doanh thu theo tháng -->
<script>
(() => {
    const mode = document.getElementById('statsPeriod');
    function syncFilters() {
        document.querySelectorAll('[data-period]').forEach(field => {
            field.hidden = field.dataset.period !== mode.value;
            field.querySelectorAll('input').forEach(input => {
                input.disabled = field.hidden;
                input.required = !field.hidden;
            });
        });
    }
    mode.addEventListener('change', syncFilters);
    syncFilters();
    const chart = document.querySelector('.dashboard-native-chart');
    const labels = <?= json_encode($revenueLabels,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
    const values = <?= json_encode($revenueData) ?>;
    const readout = document.getElementById('chartReadout');
    const money = value => new Intl.NumberFormat('vi-VN').format(Math.round(value)) + ' ₫';
    function renderChart() {
        const width = Math.max(240, Math.round(chart.getBoundingClientRect().width));
        const left = 52, right = 14, bottom = 40;
        const plotWidth = width-left-right;
        const step = plotWidth/Math.max(1,values.length);
        const laneEnds = [];
        const annotations = values.flatMap((value,index) => {
            if (value <= 0) return [];
            const x = left+index*step+step/2;
            const labelWidth = Math.max(108,money(value).length*7+12);
            const labelX = Math.max(labelWidth/2+4,Math.min(width-labelWidth/2-4,x));
            const labelLeft = labelX-labelWidth/2;
            let lane = laneEnds.findIndex(end => labelLeft > end+8);
            if (lane < 0) lane = laneEnds.length;
            laneEnds[lane] = labelX+labelWidth/2;
            return [{index,x,labelX,lane}];
        });
        const top = Math.max(62,laneEnds.length*38+12);
        const plotHeight = 168, height = top+plotHeight+bottom;
        chart.style.height = height+'px';
        const maximum = Math.max(0,...values);
        const magnitude = Math.pow(10,Math.floor(Math.log10(Math.max(1,maximum/4))));
        const tickStep = Math.max(1,Math.ceil(maximum/4/magnitude)*magnitude);
        const ceiling = tickStep*4;
        const peakIndex = values.indexOf(maximum);
        chart.setAttribute('viewBox', `0 0 ${width} ${height}`);
        chart.replaceChildren();
        const add = (tag, attributes, text) => {
            const node = document.createElementNS('http://www.w3.org/2000/svg',tag);
            Object.entries(attributes).forEach(([key,value])=>node.setAttribute(key,String(value)));
            if (text!==undefined) node.textContent=text;
            chart.appendChild(node);return node;
        };
        const compact = value => value>=1000000 ? new Intl.NumberFormat('vi-VN',{maximumFractionDigits:1}).format(value/1000000)+'tr' : value>=1000 ? new Intl.NumberFormat('vi-VN',{maximumFractionDigits:1}).format(value/1000)+'k' : String(value);
        for(let tick=0;tick<=4;tick++) {
            const y=top+plotHeight-tick/4*plotHeight;
            add('line',{x1:left,y1:y,x2:width-right,y2:y,stroke:'#363636','stroke-dasharray':tick?'3 5':'none'});
            add('text',{x:left-8,y:y+4,'text-anchor':'end',fill:'#999','font-size':11},compact(tick*tickStep));
        }
        const barWidth=Math.min(36,step*.62);
        const labelEvery=Math.max(1,Math.ceil(labels.length/Math.max(2,Math.floor(plotWidth/65))));
        values.forEach((value,index)=>{
            const x=left+index*step+step/2;
            const barHeight=value/ceiling*plotHeight;
            const description=labels[index]+' · '+money(value);
            const bar=add('rect',{x:x-barWidth/2,y:top+plotHeight-Math.max(2,barHeight),width:barWidth,height:Math.max(2,barHeight),rx:Math.min(4,barWidth/2),fill:value?'#e50914':'#353535',tabindex:0,'aria-label':description});
            const title=document.createElementNS('http://www.w3.org/2000/svg','title');title.textContent=description;bar.appendChild(title);
            ['mouseenter','focus','click'].forEach(event=>bar.addEventListener(event,()=>readout.textContent=description));
            if(index%labelEvery===0 || index===labels.length-1 && index%labelEvery>labelEvery*.7)
                add('text',{x,y:height-16,'text-anchor':'middle',fill:'#aaa','font-size':11},labels[index]);
        });
        annotations.forEach(({index,x,labelX,lane}) => {
            const y = top+plotHeight-values[index]/ceiling*plotHeight;
            const labelY = 20+lane*38;
            const peak = index === peakIndex;
            add('line',{x1:labelX,y1:labelY+20,x2:x,y2:y-5,stroke:peak?'#e50914':'#686868','stroke-width':1,'stroke-dasharray':'3 3'});
            add('text',{class:'dashboard-chart-value-date'+(peak?' dashboard-chart-peak-date':''),x:labelX,y:labelY,'text-anchor':'middle',fill:'#ddd','font-size':11},(peak?'Cao nhất · ':'')+labels[index]);
            add('text',{class:'dashboard-chart-value-amount'+(peak?' dashboard-chart-peak-amount':''),x:labelX,y:labelY+16,'text-anchor':'middle',fill:peak?'#ff4650':'#fff','font-size':12,'font-weight':700},money(values[index]));
        });
        if (maximum > 0) {
            readout.textContent = 'Doanh thu cao nhất: '+labels[peakIndex]+' · '+money(maximum)+'. Chọn cột khác để xem chi tiết.';
        }
        if(!maximum) readout.textContent='Chưa có doanh thu trong kỳ đã chọn.';
    }
    new ResizeObserver(renderChart).observe(chart.parentElement);
    renderChart();
})();
</script>
