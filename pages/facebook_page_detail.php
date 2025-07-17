<?php
require_once '../includes/config.php';

$pageId = $_GET['page_id'] ?? '';

if (empty($pageId)) {
    header('Location: facebook_pages.php');
    exit;
}

try {
    // Get basic page statistics
    $pageInfo = fetchOne("
        SELECT 
            facebook_page_id,
            COUNT(*) as total_ads,
            COUNT(DISTINCT target_url_base) as unique_targets,
            COUNT(DISTINCT ad_media_hash) as unique_media,
            MIN(first_shown_at) as first_ad_date,
            MAX(last_shown_at) as last_ad_date
        FROM facebook_ads 
        WHERE facebook_page_id = ?
    ", [$pageId]);

    if (!$pageInfo) {
        throw new Exception("Facebook Page ID not found");
    }

    // Get growth data (ads per month)
    $growthData = fetchAll("
        SELECT 
            DATE_FORMAT(first_shown_at, '%Y-%m') as month,
            COUNT(*) as ad_count
        FROM facebook_ads 
        WHERE facebook_page_id = ? AND first_shown_at IS NOT NULL
        GROUP BY DATE_FORMAT(first_shown_at, '%Y-%m')
        ORDER BY month
    ", [$pageId]);

    // Get Target URL statistics for this page
    $targetUrls = fetchAll("
        SELECT 
            target_url_base,
            COUNT(*) as usage_count,
            MIN(first_shown_at) as first_used,
            MAX(last_shown_at) as last_used
        FROM facebook_ads 
        WHERE facebook_page_id = ? AND target_url_base != ''
        GROUP BY target_url_base 
        ORDER BY usage_count DESC
    ", [$pageId]);

} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facebook Page Details - <?php echo htmlspecialchars($pageId); ?></title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header class="header">
        <div class="container">
            <h1>Facebook Ads Analytics</h1>
            <p>Facebook Page: <?php echo htmlspecialchars($pageId); ?></p>
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
        <?php if (isset($error)): ?>
        <div class="main-content">
            <div class="card" style="border-left: 4px solid #dc3545;">
                <h3>Error</h3>
                <p style="color: #dc3545;"><?php echo htmlspecialchars($error); ?></p>
                <a href="facebook_pages.php" class="btn">Back to Facebook Pages</a>
            </div>
        </div>
        <?php else: ?>
        
        <div class="main-content">
            <h2 class="page-title">Facebook Page Details</h2>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($pageInfo['total_ads']); ?></div>
                    <div class="stat-label">Total Ads</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($pageInfo['unique_targets']); ?></div>
                    <div class="stat-label">Unique Target URLs</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($pageInfo['unique_media']); ?></div>
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
                <h3 class="card-title">Target URLs for this Facebook Page</h3>
                <?php if (!empty($targetUrls)): ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Target URL</th>
                                <th>Usage Count</th>
                                <th>First Used</th>
                                <th>Last Used</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($targetUrls as $url): ?>
                            <tr>
                                <td>
                                    <a href="target_url_detail.php?url=<?php echo urlencode($url['target_url_base']); ?>">
                                        <?php echo htmlspecialchars($url['target_url_base']); ?>
                                    </a>
                                </td>
                                <td><?php echo number_format($url['usage_count']); ?></td>
                                <td><?php echo $url['first_used'] ? date('Y-m-d', strtotime($url['first_used'])) : 'N/A'; ?></td>
                                <td><?php echo $url['last_used'] ? date('Y-m-d', strtotime($url['last_used'])) : 'N/A'; ?></td>
                                <td>
                                    <a href="page_url_chart.php?page_id=<?php echo urlencode($pageId); ?>&target_url=<?php echo urlencode($url['target_url_base']); ?>" 
                                       class="btn" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                                        View Chart
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p>No target URLs found for this Facebook Page.</p>
                <?php endif; ?>
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
                            text: 'Ad Growth for Facebook Page: <?php echo htmlspecialchars($pageId); ?>'
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
    </script>
</body>
</html>

