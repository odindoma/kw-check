<?php
require_once '../includes/config.php';

// Get all Facebook Page IDs with usage counts
try {
    $pageStats = fetchAll("
        SELECT 
            facebook_page_id,
            COUNT(*) as usage_count,
            MIN(first_shown_at) as first_ad_date,
            MAX(last_shown_at) as last_ad_date
        FROM facebook_ads 
        WHERE facebook_page_id != '' 
        GROUP BY facebook_page_id 
        ORDER BY usage_count DESC
    ");
} catch (Exception $e) {
    $pageStats = [];
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facebook Pages - Facebook Ads Analytics</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header class="header">
        <div class="container">
            <h1>Facebook Ads Analytics</h1>
            <p>Facebook Page ID Statistics and Analysis</p>
        </div>
    </header>

    <nav class="nav">
        <div class="container">
            <ul>
                <li><a href="../index.php">Dashboard</a></li>
                <li><a href="facebook_pages.php" class="active">Facebook Pages</a></li>
                <li><a href="target_urls.php">Target URLs</a></li>
                <li><a href="media_hashes.php">Ad Media Hashes</a></li>
            </ul>
        </div>
    </nav>

    <main class="container">
        <div class="main-content">
            <h2 class="page-title">Facebook Page ID Statistics</h2>
            
            <?php if (isset($error)): ?>
            <div class="card" style="border-left: 4px solid #dc3545;">
                <p style="color: #dc3545;">Error loading data: <?php echo htmlspecialchars($error); ?></p>
            </div>
            <?php endif; ?>

            <?php if (!empty($pageStats)): ?>
            <div class="card">
                <h3 class="card-title">All Facebook Pages (<?php echo count($pageStats); ?> total)</h3>
                <p>Click on any Facebook Page ID to view detailed statistics and charts.</p>
                
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Facebook Page ID</th>
                                <th>Usage Count</th>
                                <th>First Ad Date</th>
                                <th>Last Ad Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pageStats as $page): ?>
                            <tr>
                                <td>
                                    <a href="facebook_page_detail.php?page_id=<?php echo urlencode($page['facebook_page_id']); ?>">
                                        <?php echo htmlspecialchars($page['facebook_page_id']); ?>
                                    </a>
                                </td>
                                <td><?php echo number_format($page['usage_count']); ?></td>
                                <td><?php echo $page['first_ad_date'] ? date('Y-m-d', strtotime($page['first_ad_date'])) : 'N/A'; ?></td>
                                <td><?php echo $page['last_ad_date'] ? date('Y-m-d', strtotime($page['last_ad_date'])) : 'N/A'; ?></td>
                                <td>
                                    <a href="facebook_page_detail.php?page_id=<?php echo urlencode($page['facebook_page_id']); ?>" class="btn" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
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
                <h3 class="card-title">Top 10 Most Used Facebook Pages</h3>
                <div class="chart-container">
                    <canvas id="topPagesChart" style="height: 400px;"></canvas>
                </div>
            </div>

            <?php else: ?>
            <div class="card">
                <h3 class="card-title">No Data Available</h3>
                <p>No Facebook Page data found. Please import some CSV files first.</p>
                <a href="../index.php" class="btn">Go to Dashboard</a>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="../assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!empty($pageStats)): ?>
            // Create top pages chart
            const topPages = <?php echo json_encode(array_slice($pageStats, 0, 10)); ?>;
            const topPagesData = {
                labels: topPages.map(page => page.facebook_page_id.substring(0, 15) + '...'),
                datasets: [{
                    label: 'Usage Count',
                    data: topPages.map(page => parseInt(page.usage_count)),
                    backgroundColor: FacebookAdsAnalytics.generateColors(topPages.length),
                    borderColor: '#667eea',
                    borderWidth: 1
                }]
            };

            FacebookAdsAnalytics.createLineChart('topPagesChart', topPagesData, {
                indexAxis: 'y',
                plugins: {
                    title: {
                        display: true,
                        text: 'Top 10 Facebook Pages by Usage Count'
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
            });
            <?php endif; ?>
        });
    </script>
</body>
</html>

