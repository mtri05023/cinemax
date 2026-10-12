<?php
// Giả sử $conn đã được khởi tạo trong index.php và include sang

$today = date('Y-m-d');
$currentYear = date('Y');

$todayRevenue    = 0;
$todayTickets    = 0;
$todayNewUsers   = 0;
$nowShowingMovies = 0;
$latestOrders    = [];
$topMovies       = [];
$monthlyRevenue  = array_fill(1, 12, 0.0);

if (isset($conn) && $conn) {
    try {
        // Doanh thu hôm nay (bills paid)
        $sql = "SELECT COALESCE(SUM(final_amount), 0) AS revenue,
                       COALESCE(SUM(total_tickets), 0) AS tickets
                FROM bills
                WHERE DATE(created_at) = ? AND status = 'paid'";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('s', $today);
            if ($stmt->execute()) {
                $res = $stmt->get_result();
                if ($res) {
                    $row = $res->fetch_assoc();
                    $todayRevenue = (float)($row['revenue'] ?? 0);
                    $todayTickets = (int)($row['tickets'] ?? 0);
                }
            }
        }

        // Người dùng mới hôm nay
        $sql = "SELECT COUNT(*) AS cnt FROM users WHERE DATE(created_at) = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('s', $today);
            if ($stmt->execute()) {
                $res = $stmt->get_result();
                if ($res) {
                    $row = $res->fetch_assoc();
                    $todayNewUsers = (int)($row['cnt'] ?? 0);
                }
            }
        }

        // Số phim đang chiếu (status = 2)
        $sql = "SELECT COUNT(*) AS cnt FROM movies WHERE status = 2";
        $result = $conn->query($sql);
        if ($result) {
            $row = $result->fetch_assoc();
            $nowShowingMovies = (int)($row['cnt'] ?? 0);
        }

        // Đơn hàng mới nhất (bills)
        $sql = "SELECT b.bill_id,
                       u.full_name,
                       b.final_amount,
                       b.status,
                       b.created_at
                FROM bills b
                INNER JOIN users u ON b.user_id = u.user_id
                ORDER BY b.created_at DESC
                LIMIT 5";
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $latestOrders[] = $row;
            }
        }

        // Top phim bán chạy theo doanh thu từ tickets
        $sql = "SELECT 
                    m.movie_id   AS MovieID,
                    m.title      AS Title,
                    m.poster_url AS poster_url,
                    COALESCE(SUM(t.price), 0) AS Revenue,
                    COUNT(t.ticket_id)        AS TicketsSold
                FROM tickets t
                INNER JOIN bills b ON t.bill_id = b.bill_id
                INNER JOIN shows s ON t.show_id = s.show_id
                INNER JOIN movies m ON s.movie_id = m.movie_id
                WHERE b.status = 'paid'
                GROUP BY m.movie_id, m.title
                ORDER BY Revenue DESC
                LIMIT 5";
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $topMovies[] = $row;
            }
        }

        // Doanh thu theo tháng trong năm hiện tại
        $sql = "SELECT 
                    MONTH(created_at) AS m,
                    COALESCE(SUM(final_amount), 0) AS revenue
                FROM bills
                WHERE YEAR(created_at) = ? AND status = 'paid'
                GROUP BY MONTH(created_at)
                ORDER BY MONTH(created_at)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('i', $currentYear);
            if ($stmt->execute()) {
                $res = $stmt->get_result();
                if ($res) {
                    while ($row = $res->fetch_assoc()) {
                        $m = (int)$row['m'];
                        if ($m >= 1 && $m <= 12) {
                            $monthlyRevenue[$m] = (float)$row['revenue'];
                        }
                    }
                }
            }
        }
    } catch (Exception $e) {
        // Không làm vỡ dashboard, chỉ giữ giá trị mặc định
    }
}

$monthlyLabels = [];
$monthlyData = [];
for ($i = 1; $i <= 12; $i++) {
    $monthlyLabels[] = 'T' . $i;
    $monthlyData[] = $monthlyRevenue[$i];
}

if (!defined('CINEMAX_BASE_URL')) {
    define('CINEMAX_BASE_URL', '/web_cinemax');
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
        <h1>Dashboard</h1>
    </header>

    <div class="dashboard-content">

        <!-- STATS -->
        <div class="stats-grid">

            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(229, 9, 20, 0.1);">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="1" x2="12" y2="23"></line>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Doanh thu hôm nay</span>
                    <span class="stat-value" style="color: #e50914;">
                        <?php echo number_format($todayRevenue, 0, ',', '.'); ?> ₫
                    </span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(70, 211, 105, 0.1);">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                    </svg>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Vé đã bán</span>
                    <span class="stat-value" style="color: #46d369;">
                        <?php echo (int)$todayTickets; ?>
                    </span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(255, 165, 0, 0.1);">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                    </svg>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Người dùng mới</span>
                    <span class="stat-value" style="color: #ffa500;">
                        <?php echo (int)$todayNewUsers; ?>
                    </span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1);">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2">
                        <rect x="2" y="7" width="20" height="15" rx="2" ry="2"></rect>
                        <polyline points="17 2 12 7 7 2"></polyline>
                    </svg>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Phim đang chiếu</span>
                    <span class="stat-value" style="color: #3b82f6;">
                        <?php echo (int)$nowShowingMovies; ?>
                    </span>
                </div>
            </div>

        </div>

        <!-- TOP MOVIES + BIỂU ĐỒ & ĐƠN HÀNG MỚI -->
        <div class="dashboard-grid-2" style="margin-top: 30px;">

            <!-- Top phim + biểu đồ doanh thu tháng -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3>Top phim bán chạy & Doanh thu theo tháng</h3>
                </div>

                <div style="margin-bottom: 24px;">
                    <canvas id="revenueByMonthChart" height="140"></canvas>
                </div>

                <div class="top-movies-list">
                    <?php if (empty($topMovies)): ?>
                        <div style="text-align:center; padding: 16px; color:#888;">
                            Chưa có dữ liệu bán vé.
                        </div>
                    <?php else: ?>
                        <?php foreach ($topMovies as $movie): ?>
                            <div class="top-movie-item">
                                <img src="<?= htmlspecialchars(getPosterUrl($movie['poster_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="tm-poster" alt="Poster">
                                <div class="tm-info">
                                    <h4><?php echo htmlspecialchars($movie['Title'] ?? ''); ?></h4>
                                    <div class="tm-stats">
                                        <span class="tm-highlight">
                                            <?php echo (int)($movie['TicketsSold'] ?? 0); ?>
                                        </span> vé đã bán
                                        <br>
                                        Doanh thu:
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
                    <h3>Đơn hàng mới nhất</h3>
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
                                <th>Thời gian</th>
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
                                        <td><?php echo htmlspecialchars($order['status'] ?? ''); ?></td>
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
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    (function() {
        const ctx = document.getElementById('revenueByMonthChart');
        if (!ctx) return;

        const labels = <?php echo json_encode($monthlyLabels, JSON_UNESCAPED_UNICODE); ?>;
        const data = <?php echo json_encode($monthlyData, JSON_NUMERIC_CHECK); ?>;

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Doanh thu (₫) - <?php echo (int)$currentYear; ?>',
                    data: data,
                    backgroundColor: 'rgba(59, 130, 246, 0.6)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1,
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('vi-VN') + ' ₫';
                            },
                            color: '#9ca3af'
                        },
                        grid: {
                            color: 'rgba(148, 163, 184, 0.15)'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#9ca3af'
                        },
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        labels: {
                            color: '#e5e7eb'
                        }
                    }
                }
            }
        });
    })();
</script>
