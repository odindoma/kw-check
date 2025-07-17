<?php
require_once '../includes/config.php';

$targetUrl = $_GET['target_url'] ?? '';
$pageId = $_GET['page_id'] ?? '';

if (empty($targetUrl) || empty($pageId)) {
    header('Location: target_urls.php');
    exit;
}

try {
    // Get basic statistics for this combination
    $stats = fetchOne("
        SELECT 
            COUNT(*) as total_ads,
            MIN(first_shown_at) as first_ad,
            MAX(last_shown_at) as last_ad,
            COUNT(DISTINCT ad_media_hash) as unique_media,
            COUNT(DISTINCT campaign) as unique_campaigns
        FROM facebook_ads 
        WHERE target_url_base = ? AND facebook_page_id = ?
    ", [$targetUrl, $pageId]);

    // Get monthly growth data
    $growthData = fetchAll("
        SELECT 
            DATE_FORMAT(first_shown_at, '%Y-%m') as month,
            COUNT(*) as ad_count
        FROM facebook_ads 
        WHERE target_url_base = ? 
        AND facebook_page_id = ? 
        AND first_shown_at IS NOT NULL
        GROUP BY DATE_FORMAT(first_shown_at, '%Y-%m')
        ORDER BY month
    ", [$targetUrl, $pageId]);

    // Get media hash breakdown
    $mediaHashes = fetchAll("
        SELECT 
            ad_media_hash,
            COUNT(*) as usage_count,
            MIN(first_shown_at) as first_used,
            MAX(last_shown_at) as last_used
        FROM facebook_ads 
        WHERE target_url_base = ? AND facebook_page_id = ? AND ad_media_hash != ''
        GROUP BY ad_media_hash 
        ORDER BY usage_count DESC
        LIMIT 10
    ", [$targetUrl, $pageId]);

    // Get region breakdown
    $regions = fetchAll("
        SELECT 
            region,
            COUNT(*) as ad_count
        FROM facebook_ads 
        WHERE target_url_base = ? AND facebook_page_id = ? AND region != ''
        GROUP BY region 
        ORDER BY ad_count DESC
        LIMIT 10
    ", [$targetUrl, $pageId]);

} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>URL + Page Analysis - Facebook Ads Analytics</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header class="header">
        <div class="container">
            <h1>Facebook Ads Analytics</h1>
            <p>URL + Page Combination Analysis</p>
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
                <a href="target_urls.php" class="btn">Back to Target URLs</a>
            </div>
        </div>
        <?php else: ?>
        
        <div class="main-content">
            <h2 class="page-title">URL + Page Analysis</h2>
            
            <div class="card">
                <h3 class="card-title">Combination Details</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin: 1rem 0;">
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
                <?php if (!empty($regions)): ?>
                <div class="card">
                    <h3 class="card-title">Regional Distribution</h3>
                    <div class="chart-container">
                        <canvas id="regionChart" style="height: 300px;"></canvas>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($mediaHashes) && count($mediaHashes) > 1): ?>
                <div class="card">
                    <h3 class="card-title">Media Hash Usage</h3>
                    <div class="chart-container">
                        <canvas id="mediaChart" style="height: 300px;"></canvas>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($mediaHashes)): ?>
            <div class="card">
                <h3 class="card-title">Media Hashes for this Combination</h3>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Media Hash</th>
                                <th>Usage Count</th>
                                <th>First Used</th>
                                <th>Last Used</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mediaHashes as $media): ?>
                            <tr>
                                <td>
                                    <code style="font-size: 0.8rem;">
                                        <?php echo htmlspecialchars(substr($media['ad_media_hash'], 0, 20)); ?>...
                                    </code>
                                </td>
                                <td><?php echo number_format($media['usage_count']); ?></td>
                                <td><?php echo $media['first_used'] ? date('Y-m-d', strtotime($media['first_used'])) : 'N/A'; ?></td>
                                <td><?php echo $media['last_used'] ? date('Y-m-d', strtotime($media['last_used'])) : 'N/A'; ?></td>
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
                            text: 'Monthly Ad Growth for URL + Page Combination'
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

            <?php if (!isset($error) && !empty($regions)): ?>
            // Create region distribution pie chart
            const regions = <?php echo json_encode($regions); ?>;
            const regionData = {
                labels: regions.map(r => r.region),
                datasets: [{
                    data: regions.map(r => parseInt(r.ad_count)),
                    backgroundColor: FacebookAdsAnalytics.generateColors(regions.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            };

            FacebookAdsAnalytics.createPieChart('regionChart', regionData, {
                plugins: {
                    title: {
                        display: true,
                        text: 'Regional Distribution'
                    }
                }
            });
            <?php endif; ?>

            <?php if (!isset($error) && !empty($mediaHashes) && count($mediaHashes) > 1): ?>
            // Create media hash distribution chart
            const mediaHashes = <?php echo json_encode($mediaHashes); ?>;
            const mediaData = {
                labels: mediaHashes.map(m => m.ad_media_hash.substring(0, 8) + '...'),
                datasets: [{
                    data: mediaHashes.map(m => parseInt(m.usage_count)),
                    backgroundColor: FacebookAdsAnalytics.generateColors(mediaHashes.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            };

            FacebookAdsAnalytics.createPieChart('mediaChart', mediaData, {
                plugins: {
                    title: {
                        display: true,
                        text: 'Media Hash Distribution'
                    }
                }
            });
            <?php endif; ?>
        });
    </script>
</body>
</html>

