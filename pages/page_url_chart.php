<?php
require_once '../includes/config.php';

$pageId = $_GET['page_id'] ?? '';
$targetUrl = $_GET['target_url'] ?? '';

if (empty($pageId) || empty($targetUrl)) {
    header('Location: facebook_pages.php');
    exit;
}

try {
    // Get basic statistics for this combination
    $stats = fetchOne("
        SELECT 
            COUNT(*) as total_ads,
            MIN(first_shown_at) as first_ad,
            MAX(last_shown_at) as last_ad,
            COUNT(DISTINCT ad_media_hash) as unique_media
        FROM facebook_ads 
        WHERE facebook_page_id = ? AND target_url_base = ?
    ", [$pageId, $targetUrl]);

    // Get monthly growth data
    $growthData = fetchAll("
        SELECT 
            DATE_FORMAT(first_shown_at, '%Y-%m') as month,
            COUNT(*) as ad_count
        FROM facebook_ads 
        WHERE facebook_page_id = ? 
        AND target_url_base = ? 
        AND first_shown_at IS NOT NULL
        GROUP BY DATE_FORMAT(first_shown_at, '%Y-%m')
        ORDER BY month
    ", [$pageId, $targetUrl]);

    // Get campaign breakdown
    $campaigns = fetchAll("
        SELECT 
            campaign,
            COUNT(*) as ad_count,
            MIN(first_shown_at) as first_shown,
            MAX(last_shown_at) as last_shown
        FROM facebook_ads 
        WHERE facebook_page_id = ? AND target_url_base = ?
        GROUP BY campaign 
        ORDER BY ad_count DESC
        LIMIT 10
    ", [$pageId, $targetUrl]);

} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page + URL Analysis - Facebook Ads Analytics</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header class="header">
        <div class="container">
            <h1>Facebook Ads Analytics</h1>
            <p>Page + URL Combination Analysis</p>
        </div>
    </header>

    <nav class="nav">
        <div class="container">
            <ul>
                <li><a href="../index.php">Dashboard</a></li>
                <li><a href="facebook_pages.php">Facebook Pages</a></li>
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
            <h2 class="page-title">Page + URL Analysis</h2>
            
            <div class="card">
                <h3 class="card-title">Combination Details</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin: 1rem 0;">
                    <div>
                        <strong>Facebook Page ID:</strong><br>
                        <code style="font-size: 0.9rem; background: #f8f9fa; padding: 0.25rem 0.5rem; border-radius: 4px;">
                            <?php echo htmlspecialchars($pageId); ?>
                        </code>
                        <br><br>
                        <a href="facebook_page_detail.php?page_id=<?php echo urlencode($pageId); ?>" class="btn btn-secondary">
                            View Page Details
                        </a>
                    </div>
                    <div>
                        <strong>Target URL:</strong><br>
                        <code style="font-size: 0.9rem; background: #f8f9fa; padding: 0.25rem 0.5rem; border-radius: 4px;">
                            <?php echo htmlspecialchars($targetUrl); ?>
                        </code>
                        <br><br>
                        <a href="target_url_detail.php?url=<?php echo urlencode($targetUrl); ?>" class="btn btn-secondary">
                            View URL Details
                        </a>
                    </div>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['total_ads']); ?></div>
                    <div class="stat-label">Total Ads</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['unique_media']); ?></div>
                    <div class="stat-label">Unique Media</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['first_ad'] ? date('M Y', strtotime($stats['first_ad'])) : 'N/A'; ?></div>
                    <div class="stat-label">First Ad</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['last_ad'] ? date('M Y', strtotime($stats['last_ad'])) : 'N/A'; ?></div>
                    <div class="stat-label">Last Ad</div>
                </div>
            </div>

            <?php if (!empty($growthData)): ?>
            <div class="card">
                <h3 class="card-title">Monthly Ad Growth</h3>
                <div class="chart-container">
                    <canvas id="growthChart" style="height: 400px;"></canvas>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($campaigns)): ?>
            <div class="card">
                <h3 class="card-title">Top Campaigns for this Combination</h3>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Campaign</th>
                                <th>Ad Count</th>
                                <th>First Shown</th>
                                <th>Last Shown</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($campaigns as $campaign): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($campaign['campaign']); ?></td>
                                <td><?php echo number_format($campaign['ad_count']); ?></td>
                                <td><?php echo $campaign['first_shown'] ? date('Y-m-d', strtotime($campaign['first_shown'])) : 'N/A'; ?></td>
                                <td><?php echo $campaign['last_shown'] ? date('Y-m-d', strtotime($campaign['last_shown'])) : 'N/A'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <div class="card">
                <h3 class="card-title">Campaign Distribution</h3>
                <div class="chart-container">
                    <canvas id="campaignChart" style="height: 400px;"></canvas>
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
                            text: 'Monthly Ad Growth for Page + URL Combination'
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

            <?php if (!isset($error) && !empty($campaigns)): ?>
            // Create campaign distribution pie chart
            const campaigns = <?php echo json_encode($campaigns); ?>;
            const campaignData = {
                labels: campaigns.map(c => c.campaign.length > 30 ? c.campaign.substring(0, 30) + '...' : c.campaign),
                datasets: [{
                    data: campaigns.map(c => parseInt(c.ad_count)),
                    backgroundColor: FacebookAdsAnalytics.generateColors(campaigns.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            };

            FacebookAdsAnalytics.createPieChart('campaignChart', campaignData, {
                plugins: {
                    title: {
                        display: true,
                        text: 'Campaign Distribution for this Page + URL'
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            });
            <?php endif; ?>
        });
    </script>
</body>
</html>

