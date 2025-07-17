<?php
require_once '../includes/config.php';

$mediaHash = $_GET['hash'] ?? '';

if (empty($mediaHash)) {
    header('Location: media_hashes.php');
    exit;
}

try {
    // Get basic statistics for this media hash
    $stats = fetchOne("
        SELECT 
            COUNT(*) as total_usage,
            COUNT(DISTINCT facebook_page_id) as unique_pages,
            COUNT(DISTINCT target_url_base) as unique_urls,
            COUNT(DISTINCT campaign) as unique_campaigns,
            MIN(first_shown_at) as first_used,
            MAX(last_shown_at) as last_used
        FROM facebook_ads 
        WHERE ad_media_hash = ?
    ", [$mediaHash]);

    // Get Facebook Pages using this media hash
    $pages = fetchAll("
        SELECT 
            facebook_page_id,
            COUNT(*) as usage_count,
            MIN(first_shown_at) as first_used,
            MAX(last_shown_at) as last_used
        FROM facebook_ads 
        WHERE ad_media_hash = ? AND facebook_page_id != ''
        GROUP BY facebook_page_id 
        ORDER BY usage_count DESC
        LIMIT 20
    ", [$mediaHash]);

    // Get usage timeline for this media hash
    $timeline = fetchAll("
        SELECT 
            DATE_FORMAT(first_shown_at, '%Y-%m') as month,
            COUNT(*) as ad_count
        FROM facebook_ads 
        WHERE ad_media_hash = ? AND first_shown_at IS NOT NULL
        GROUP BY DATE_FORMAT(first_shown_at, '%Y-%m')
        ORDER BY month
    ", [$mediaHash]);

    // Get target URLs using this media hash
    $targetUrls = fetchAll("
        SELECT 
            target_url_base,
            COUNT(*) as usage_count,
            COUNT(DISTINCT facebook_page_id) as unique_pages
        FROM facebook_ads 
        WHERE ad_media_hash = ? AND target_url_base != ''
        GROUP BY target_url_base 
        ORDER BY usage_count DESC
        LIMIT 15
    ", [$mediaHash]);

    // Get campaigns using this media hash
    $campaigns = fetchAll("
        SELECT 
            campaign,
            COUNT(*) as usage_count,
            COUNT(DISTINCT facebook_page_id) as unique_pages
        FROM facebook_ads 
        WHERE ad_media_hash = ? AND campaign != ''
        GROUP BY campaign 
        ORDER BY usage_count DESC
        LIMIT 10
    ", [$mediaHash]);

} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media Hash Details - Facebook Ads Analytics</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header class="header">
        <div class="container">
            <h1>Facebook Ads Analytics</h1>
            <p>Media Hash Analysis</p>
        </div>
    </header>

    <nav class="nav">
        <div class="container">
            <ul>
                <li><a href="../index.php">Dashboard</a></li>
                <li><a href="facebook_pages.php">Facebook Pages</a></li>
                <li><a href="target_urls.php">Target URLs</a></li>
                <li><a href="media_hashes.php" class="active">Ad Media Hashes</a></li>
            </ul>
        </div>
    </nav>

    <main class="container">
        <?php if (isset($error)): ?>
        <div class="main-content">
            <div class="card" style="border-left: 4px solid #dc3545;">
                <h3>Error</h3>
                <p style="color: #dc3545;"><?php echo htmlspecialchars($error); ?></p>
                <a href="media_hashes.php" class="btn">Back to Media Hashes</a>
            </div>
        </div>
        <?php else: ?>
        
        <div class="main-content">
            <h2 class="page-title">Media Hash Details</h2>
            
            <div class="card">
                <h3 class="card-title">Media Hash Information</h3>
                <div style="margin: 1rem 0;">
                    <strong>Hash:</strong><br>
                    <code style="font-size: 0.9rem; background: #f8f9fa; padding: 0.5rem; border-radius: 4px; word-break: break-all; display: block; margin: 0.5rem 0;">
                        <?php echo htmlspecialchars($mediaHash); ?>
                    </code>
                </div>
                <a href="media_hashes.php" class="btn btn-secondary">Back to All Media Hashes</a>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['total_usage']); ?></div>
                    <div class="stat-label">Total Usage</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['unique_pages']); ?></div>
                    <div class="stat-label">Unique Pages</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['unique_urls']); ?></div>
                    <div class="stat-label">Unique URLs</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['unique_campaigns']); ?></div>
                    <div class="stat-label">Campaigns</div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin: 2rem 0;">
                <?php if (!empty($pages)): ?>
                <div class="card">
                    <h3 class="card-title">Facebook Page Distribution</h3>
                    <div class="chart-container">
                        <canvas id="pageDistributionChart" style="height: 300px;"></canvas>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($timeline)): ?>
                <div class="card">
                    <h3 class="card-title">Usage Over Time</h3>
                    <div class="chart-container">
                        <canvas id="usageTimeChart" style="height: 300px;"></canvas>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($pages)): ?>
            <div class="card">
                <h3 class="card-title">Facebook Pages using this Media Hash</h3>
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
                            <?php foreach ($pages as $page): ?>
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
                                    <a href="page_media_chart.php?page_id=<?php echo urlencode($page['facebook_page_id']); ?>&hash=<?php echo urlencode($mediaHash); ?>" 
                                       class="btn" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                                        View Chart
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($targetUrls)): ?>
            <div class="card">
                <h3 class="card-title">Target URLs using this Media Hash</h3>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Target URL</th>
                                <th>Usage Count</th>
                                <th>Unique Pages</th>
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
                                <td><?php echo number_format($url['unique_pages']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($campaigns)): ?>
            <div class="card">
                <h3 class="card-title">Top Campaigns using this Media Hash</h3>
                <div class="chart-container">
                    <canvas id="campaignChart" style="height: 400px;"></canvas>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <?php endif; ?>
    </main>

    <script src="../assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!isset($error) && !empty($pages)): ?>
            // Create page distribution pie chart
            const pages = <?php echo json_encode(array_slice($pages, 0, 10)); ?>;
            const pageData = {
                labels: pages.map(page => page.facebook_page_id.substring(0, 15) + '...'),
                datasets: [{
                    data: pages.map(page => parseInt(page.usage_count)),
                    backgroundColor: FacebookAdsAnalytics.generateColors(pages.length),
                    borderWidth: 1,
                    borderColor: '#fff'
                }]
            };

            FacebookAdsAnalytics.createPieChart('pageDistributionChart', pageData, {
                plugins: {
                    title: {
                        display: true,
                        text: 'Top 10 Pages by Usage'
                    }
                }
            });
            <?php endif; ?>

            <?php if (!isset($error) && !empty($timeline)): ?>
            // Create usage over time chart
            const timeline = <?php echo json_encode($timeline); ?>;
            const timeData = {
                labels: timeline.map(item => item.month),
                datasets: [{
                    label: 'Usage Count',
                    data: timeline.map(item => parseInt(item.ad_count)),
                    backgroundColor: 'rgba(102, 126, 234, 0.2)',
                    borderColor: '#667eea',
                    borderWidth: 2,
                    fill: true
                }]
            };

            FacebookAdsAnalytics.createLineChart('usageTimeChart', timeData, {
                plugins: {
                    title: {
                        display: true,
                        text: 'Usage Over Time'
                    }
                }
            });
            <?php endif; ?>

            <?php if (!isset($error) && !empty($campaigns)): ?>
            // Create campaign distribution chart
            const campaigns = <?php echo json_encode($campaigns); ?>;
            const campaignData = {
                labels: campaigns.map(c => c.campaign.length > 25 ? c.campaign.substring(0, 25) + '...' : c.campaign),
                datasets: [{
                    label: 'Usage Count',
                    data: campaigns.map(c => parseInt(c.usage_count)),
                    backgroundColor: FacebookAdsAnalytics.generateColors(campaigns.length),
                    borderColor: '#667eea',
                    borderWidth: 1
                }]
            };

            new Chart(document.getElementById('campaignChart'), {
                type: 'bar',
                data: campaignData,
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Top Campaigns using this Media Hash'
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

