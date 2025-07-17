<?php
require_once '../includes/config.php';

$pageId = $_GET['page_id'] ?? '';
$mediaHash = $_GET['hash'] ?? '';

if (empty($pageId) || empty($mediaHash)) {
    header('Location: media_hashes.php');
    exit;
}

try {
    // Get basic statistics for this combination
    $stats = fetchOne("
        SELECT 
            COUNT(*) as total_ads,
            MIN(first_shown_at) as first_ad,
            MAX(last_shown_at) as last_ad,
            COUNT(DISTINCT target_url_base) as unique_urls,
            COUNT(DISTINCT campaign) as unique_campaigns
        FROM facebook_ads 
        WHERE facebook_page_id = ? AND ad_media_hash = ?
    ", [$pageId, $mediaHash]);

    // Get monthly growth data
    $growthData = fetchAll("
        SELECT 
            DATE_FORMAT(first_shown_at, '%Y-%m') as month,
            COUNT(*) as ad_count
        FROM facebook_ads 
        WHERE facebook_page_id = ? 
        AND ad_media_hash = ? 
        AND first_shown_at IS NOT NULL
        GROUP BY DATE_FORMAT(first_shown_at, '%Y-%m')
        ORDER BY month
    ", [$pageId, $mediaHash]);

    // Get target URL breakdown
    $targetUrls = fetchAll("
        SELECT 
            target_url_base,
            COUNT(*) as usage_count,
            MIN(first_shown_at) as first_used,
            MAX(last_shown_at) as last_used
        FROM facebook_ads 
        WHERE facebook_page_id = ? AND ad_media_hash = ? AND target_url_base != ''
        GROUP BY target_url_base 
        ORDER BY usage_count DESC
        LIMIT 10
    ", [$pageId, $mediaHash]);

    // Get campaign breakdown
    $campaigns = fetchAll("
        SELECT 
            campaign,
            COUNT(*) as ad_count,
            MIN(first_shown_at) as first_shown,
            MAX(last_shown_at) as last_shown
        FROM facebook_ads 
        WHERE facebook_page_id = ? AND ad_media_hash = ? AND campaign != ''
        GROUP BY campaign 
        ORDER BY ad_count DESC
        LIMIT 10
    ", [$pageId, $mediaHash]);

} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page + Media Hash Analysis - Facebook Ads Analytics</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header class="header">
        <div class="container">
            <h1>Facebook Ads Analytics</h1>
            <p>Page + Media Hash Combination Analysis</p>
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
                <a href="media_hashes.php" class="btn">Back to Media Hashes</a>
            </div>
        </div>
        <?php else: ?>
        
        <div class="main-content">
            <h2 class="page-title">Page + Media Hash Analysis</h2>
            
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
                        <strong>Media Hash:</strong><br>
                        <code style="font-size: 0.8rem; background: #f8f9fa; padding: 0.25rem 0.5rem; border-radius: 4px; word-break: break-all; display: block; margin: 0.5rem 0;">
                            <?php echo htmlspecialchars(substr($mediaHash, 0, 40)); ?>...
                        </code>
                        <a href="media_hash_detail.php?hash=<?php echo urlencode($mediaHash); ?>" class="btn btn-secondary">
                            View Media Details
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
                    <div class="stat-number"><?php echo number_format($stats['unique_urls']); ?></div>
                    <div class="stat-label">Unique URLs</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['unique_campaigns']); ?></div>
                    <div class="stat-label">Campaigns</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?php 
                        if ($stats['first_ad'] && $stats['last_ad']) {
                            $days = (strtotime($stats['last_ad']) - strtotime($stats['first_ad'])) / (60 * 60 * 24);
                            echo number_format($days);
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </div>
                    <div class="stat-label">Active Days</div>
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

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin: 2rem 0;">
                <?php if (!empty($targetUrls) && count($targetUrls) > 1): ?>
                <div class="card">
                    <h3 class="card-title">Target URL Distribution</h3>
                    <div class="chart-container">
                        <canvas id="urlChart" style="height: 300px;"></canvas>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($campaigns) && count($campaigns) > 1): ?>
                <div class="card">
                    <h3 class="card-title">Campaign Distribution</h3>
                    <div class="chart-container">
                        <canvas id="campaignChart" style="height: 300px;"></canvas>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($targetUrls)): ?>
            <div class="card">
                <h3 class="card-title">Target URLs for this Combination</h3>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Target URL</th>
                                <th>Usage Count</th>
                                <th>First Used</th>
                                <th>Last Used</th>
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
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($campaigns)): ?>
            <div class="card">
                <h3 class="card-title">Campaigns for this Combination</h3>
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
        </div>

        <?php endif; ?>
    </main>

    <script src="../assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!isset($error) && !empty($growthData)): ?>
            // Create growth chart
            const growthData = <?php echo json_encode($growthData); ?>;
            
            const chartData = {
                labels: growthData.map(item => item.month),
                datasets: [{
                    label: 'Ads Count',
                    data: growthData.map(item => parseInt(item.ad_count)),
                    backgroundColor: 'rgba(102, 126, 234, 0.2)',
                    borderColor: '#667eea',
                    borderWidth: 2,
                    fill: true
                }]
            };

            FacebookAdsAnalytics.createLineChart('growthChart', chartData, {
                plugins: {
                    title: {
                        display: true,
                        text: 'Monthly Ad Count for Page + Media Hash Combination'
                    }
                }
            });
            <?php endif; ?>

            <?php if (!isset($error) && !empty($targetUrls) && count($targetUrls) > 1): ?>
            // Create URL distribution pie chart
            const targetUrls = <?php echo json_encode($targetUrls); ?>;
            const urlData = {
                labels: targetUrls.map(url => {
                    const parts = url.target_url_base.split('/');
                    return parts[parts.length - 1] || parts[parts.length - 2] || url.target_url_base;
                }),
                datasets: [{
                    data: targetUrls.map(url => parseInt(url.usage_count)),
                    backgroundColor: FacebookAdsAnalytics.generateColors(targetUrls.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            };

            FacebookAdsAnalytics.createPieChart('urlChart', urlData, {
                plugins: {
                    title: {
                        display: true,
                        text: 'Target URL Distribution'
                    }
                }
            });
            <?php endif; ?>

            <?php if (!isset($error) && !empty($campaigns) && count($campaigns) > 1): ?>
            // Create campaign distribution pie chart
            const campaigns = <?php echo json_encode($campaigns); ?>;
            const campaignData = {
                labels: campaigns.map(c => c.campaign.length > 20 ? c.campaign.substring(0, 20) + '...' : c.campaign),
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
                        text: 'Campaign Distribution'
                    }
                }
            });
            <?php endif; ?>
        });
    </script>
</body>
</html>

