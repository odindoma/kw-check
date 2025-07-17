<?php
require_once '../includes/config.php';

// Get all Target URLs with ad counts
try {
    $urlStats = fetchAll("
        SELECT 
            target_url_base,
            COUNT(*) as ad_count,
            COUNT(DISTINCT facebook_page_id) as unique_pages,
            MIN(first_shown_at) as first_ad_date,
            MAX(last_shown_at) as last_ad_date
        FROM facebook_ads 
        WHERE target_url_base != '' 
        GROUP BY target_url_base 
        ORDER BY ad_count DESC
    ");
} catch (Exception $e) {
    $urlStats = [];
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Target URLs - Facebook Ads Analytics</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header class="header">
        <div class="container">
            <h1>Facebook Ads Analytics</h1>
            <p>Target URL Statistics and Analysis</p>
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
        <div class="main-content">
            <h2 class="page-title">Target URL Statistics</h2>
            
            <?php if (isset($error)): ?>
            <div class="card" style="border-left: 4px solid #dc3545;">
                <p style="color: #dc3545;">Error loading data: <?php echo htmlspecialchars($error); ?></p>
            </div>
            <?php endif; ?>

            <?php if (!empty($urlStats)): ?>
            <div class="card">
                <h3 class="card-title">All Target URLs (<?php echo count($urlStats); ?> total)</h3>
                <p>Click on any Target URL to view detailed statistics and charts.</p>
                
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Target URL</th>
                                <th>Ad Count</th>
                                <th>Unique Pages</th>
                                <th>First Ad Date</th>
                                <th>Last Ad Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($urlStats as $url): ?>
                            <tr>
                                <td>
                                    <a href="target_url_detail.php?url=<?php echo urlencode($url['target_url_base']); ?>">
                                        <?php echo htmlspecialchars($url['target_url_base']); ?>
                                    </a>
                                </td>
                                <td><?php echo number_format($url['ad_count']); ?></td>
                                <td><?php echo number_format($url['unique_pages']); ?></td>
                                <td><?php echo $url['first_ad_date'] ? date('Y-m-d', strtotime($url['first_ad_date'])) : 'N/A'; ?></td>
                                <td><?php echo $url['last_ad_date'] ? date('Y-m-d', strtotime($url['last_ad_date'])) : 'N/A'; ?></td>
                                <td>
                                    <a href="target_url_detail.php?url=<?php echo urlencode($url['target_url_base']); ?>" class="btn" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <h3 class="card-title">Top 10 Most Advertised URLs</h3>
                <div class="chart-container">
                    <canvas id="topUrlsChart" style="height: 400px;"></canvas>
                </div>
            </div>

            <?php else: ?>
            <div class="card">
                <h3 class="card-title">No Data Available</h3>
                <p>No Target URL data found. Please import some CSV files first.</p>
                <a href="../index.php" class="btn">Go to Dashboard</a>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="../assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!empty($urlStats)): ?>
            // Create top URLs chart
            const topUrls = <?php echo json_encode(array_slice($urlStats, 0, 10)); ?>;
            const topUrlsData = {
                labels: topUrls.map(url => {
                    const urlParts = url.target_url_base.split('/');
                    return urlParts[urlParts.length - 1] || urlParts[urlParts.length - 2] || url.target_url_base;
                }),
                datasets: [{
                    label: 'Ad Count',
                    data: topUrls.map(url => parseInt(url.ad_count)),
                    backgroundColor: FacebookAdsAnalytics.generateColors(topUrls.length),
                    borderColor: '#667eea',
                    borderWidth: 1
                }]
            };

            new Chart(document.getElementById('topUrlsChart'), {
                type: 'bar',
                data: topUrlsData,
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Top 10 Target URLs by Ad Count'
                        },
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
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
    </script>
</body>
</html>

