<?php
require_once '../includes/config.php';

$targetUrl = $_GET['url'] ?? '';

if (empty($targetUrl)) {
    header('Location: target_urls.php');
    exit;
}

try {
    // Get basic URL statistics
    $urlInfo = fetchOne("
        SELECT 
            target_url_base,
            COUNT(*) as total_ads,
            COUNT(DISTINCT facebook_page_id) as unique_pages,
            COUNT(DISTINCT ad_media_hash) as unique_media,
            MIN(first_shown_at) as first_ad_date,
            MAX(last_shown_at) as last_ad_date
        FROM facebook_ads 
        WHERE target_url_base = ?
    ", [$targetUrl]);

    if (!$urlInfo) {
        throw new Exception("Target URL not found");
    }

    // Get growth data (ads per month)
    $growthData = fetchAll("
        SELECT 
            DATE_FORMAT(first_shown_at, '%Y-%m') as month,
            COUNT(*) as ad_count
        FROM facebook_ads 
        WHERE target_url_base = ? AND first_shown_at IS NOT NULL
        GROUP BY DATE_FORMAT(first_shown_at, '%Y-%m')
        ORDER BY month
    ", [$targetUrl]);

    // Get Facebook Page statistics for this URL
    $facebookPages = fetchAll("
        SELECT 
            facebook_page_id,
            COUNT(*) as usage_count,
            MIN(first_shown_at) as first_used,
            MAX(last_shown_at) as last_used
        FROM facebook_ads 
        WHERE target_url_base = ? AND facebook_page_id != ''
        GROUP BY facebook_page_id 
        ORDER BY usage_count DESC
    ", [$targetUrl]);

} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Target URL Details - <?php echo htmlspecialchars($targetUrl); ?></title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header class="header">
        <div class="container">
            <h1>Facebook Ads Analytics</h1>
            <p>Target URL: <?php echo htmlspecialchars($targetUrl); ?></p>
        </div>
    </header>

    <nav class="nav">
        <div class="container">
            <ul>
                <li><a href="../index.php">Dashboard</a></li>
                <li><a href="facebook_pages.php">Facebook Pages</a></li>
                <li><a href="target_urls.php" class="active">Target URLs</a></li>
                <li><a href="media_hashes.php">Ad Media Hashes</a></li>
            </ul>
        </div>
    </nav>

    <main class="container">
        <?php if (isset($error)): ?>
        <div class="main-content">
            <div class="card" style="border-left: 4px solid #dc3545;">
                <h3>Error</h3>
                <p style="color: #dc3545;"><?php echo htmlspecialchars($error); ?></p>
                <a href="target_urls.php" class="btn">Back to Target URLs</a>
            </div>
        </div>
        <?php else: ?>
        
        <div class="main-content">
            <h2 class="page-title">Target URL Details</h2>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($urlInfo['total_ads']); ?></div>
                    <div class="stat-label">Total Ads</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($urlInfo['unique_pages']); ?></div>
                    <div class="stat-label">Unique Facebook Pages</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($urlInfo['unique_media']); ?></div>
                    <div class="stat-label">Unique Media Hashes</div>
                </div>
            </div>

            <div class="card">
                <h3 class="card-title">Ad Growth Over Time</h3>
                <div class="chart-container">
                    <canvas id="growthChart" style="height: 400px;"></canvas>
                </div>
            </div>

            <div class="card">
                <h3 class="card-title">Facebook Pages using this Target URL</h3>
                <?php if (!empty($facebookPages)): ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Facebook Page ID</th>
                                <th>Usage Count</th>
                                <th>First Used</th>
                                <th>Last Used</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($facebookPages as $page): ?>
                            <tr>
                                <td>
                                    <a href="facebook_page_detail.php?page_id=<?php echo urlencode($page['facebook_page_id']); ?>">
                                        <?php echo htmlspecialchars($page['facebook_page_id']); ?>
                                    </a>
                                </td>
                                <td><?php echo number_format($page['usage_count']); ?></td>
                                <td><?php echo $page['first_used'] ? date('Y-m-d', strtotime($page['first_used'])) : 'N/A'; ?></td>
                                <td><?php echo $page['last_used'] ? date('Y-m-d', strtotime($page['last_used'])) : 'N/A'; ?></td>
                                <td>
                                    <button class="btn" style="padding: 0.5rem 1rem; font-size: 0.9rem;" 
                                            onclick="showUrlPageChart('<?php echo htmlspecialchars($targetUrl); ?>', '<?php echo htmlspecialchars($page['facebook_page_id']); ?>')">
                                        View Chart
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p>No Facebook Pages found for this Target URL.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Modal for URL + Page Chart -->
        <div id="urlPageModal" class="modal">
            <div class="modal-content">
                <span class="modal-close" onclick="FacebookAdsAnalytics.closeModal('urlPageModal')">&times;</span>
                <h3 id="modalTitle">Growth Chart</h3>
                <div class="chart-container">
                    <canvas id="urlPageChart" style="height: 400px;"></canvas>
                </div>
            </div>
        </div>

        <?php endif; ?>
    </main>

    <script src="../assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!isset($error) && !empty($growthData)): ?>
            // Create growth chart
            const growthData = <?php echo json_encode($growthData); ?>;
            
            // Calculate cumulative data
            let cumulative = 0;
            const cumulativeData = growthData.map(item => {
                cumulative += parseInt(item.ad_count);
                return cumulative;
            });

            const chartData = {
                labels: growthData.map(item => item.month),
                datasets: [
                    {
                        label: 'New Ads per Month',
                        data: growthData.map(item => parseInt(item.ad_count)),
                        backgroundColor: 'rgba(102, 126, 234, 0.2)',
                        borderColor: '#667eea',
                        borderWidth: 2,
                        type: 'bar'
                    },
                    {
                        label: 'Cumulative Ads',
                        data: cumulativeData,
                        backgroundColor: 'rgba(118, 75, 162, 0.2)',
                        borderColor: '#764ba2',
                        borderWidth: 2,
                        type: 'line'
                    }
                ]
            };

            new Chart(document.getElementById('growthChart'), {
                type: 'bar',
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Ad Growth for Target URL'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
            <?php endif; ?>
        });

        function showUrlPageChart(targetUrl, pageId) {
            FacebookAdsAnalytics.openModal('urlPageModal');
            document.getElementById('modalTitle').textContent = 'Growth Chart: ' + targetUrl + ' ← ' + pageId;
            
            // Load chart data via AJAX (reuse the same endpoint)
            FacebookAdsAnalytics.makeAjaxRequest(
                'ajax/page_url_chart.php',
                { page_id: pageId, target_url: targetUrl },
                function(response) {
                    if (response.success) {
                        const chartData = {
                            labels: response.data.map(item => item.month),
                            datasets: [{
                                label: 'Ads Count',
                                data: response.data.map(item => parseInt(item.ad_count)),
                                backgroundColor: 'rgba(102, 126, 234, 0.2)',
                                borderColor: '#667eea',
                                borderWidth: 2
                            }]
                        };

                        // Destroy existing chart if it exists
                        if (window.urlPageChart) {
                            window.urlPageChart.destroy();
                        }

                        window.urlPageChart = FacebookAdsAnalytics.createLineChart('urlPageChart', chartData, {
                            plugins: {
                                title: {
                                    display: true,
                                    text: 'Monthly Ad Count for URL + Page Combination'
                                }
                            }
                        });
                    } else {
                        document.getElementById('urlPageChart').parentElement.innerHTML = '<p>Error loading chart data</p>';
                    }
                },
                function(error) {
                    document.getElementById('urlPageChart').parentElement.innerHTML = '<p>Error: ' + error + '</p>';
                }
            );
        }
    </script>
</body>
</html>

