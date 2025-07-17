<?php
require_once '../includes/config.php';

try {
    // Get usage distribution data for pie chart
    $usageDistribution = fetchAll("
        SELECT 
            usage_count,
            COUNT(*) as hash_count
        FROM (
            SELECT 
                ad_media_hash,
                COUNT(*) as usage_count
            FROM facebook_ads 
            WHERE ad_media_hash != '' 
            GROUP BY ad_media_hash
        ) as hash_usage
        GROUP BY usage_count
        ORDER BY usage_count
    ");

    // Get all media hashes with usage counts
    $mediaHashes = fetchAll("
        SELECT 
            ad_media_hash,
            COUNT(*) as usage_count,
            COUNT(DISTINCT facebook_page_id) as unique_pages,
            MIN(first_shown_at) as first_used,
            MAX(last_shown_at) as last_used
        FROM facebook_ads 
        WHERE ad_media_hash != '' 
        GROUP BY ad_media_hash 
        ORDER BY usage_count DESC
    ");

} catch (Exception $e) {
    $usageDistribution = [];
    $mediaHashes = [];
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ad Media Hashes - Facebook Ads Analytics</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header class="header">
        <div class="container">
            <h1>Facebook Ads Analytics</h1>
            <p>Ad Media Hash Statistics and Distribution Analysis</p>
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
        <div class="main-content">
            <h2 class="page-title">Ad Media Hash Statistics</h2>
            
            <?php if (isset($error)): ?>
            <div class="card" style="border-left: 4px solid #dc3545;">
                <p style="color: #dc3545;">Error loading data: <?php echo htmlspecialchars($error); ?></p>
            </div>
            <?php endif; ?>

            <?php if (!empty($usageDistribution)): ?>
            <div class="card">
                <h3 class="card-title">Usage Distribution Analysis</h3>
                <p>This chart shows the percentage distribution of how many times each media hash is used across all ads.</p>
                <div class="chart-container">
                    <canvas id="distributionChart" style="height: 400px;"></canvas>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($mediaHashes)): ?>
            <div class="card">
                <h3 class="card-title">All Ad Media Hashes (<?php echo count($mediaHashes); ?> total)</h3>
                <p>Click on any media hash to view detailed statistics and Facebook Page distribution.</p>
                
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Media Hash</th>
                                <th>Usage Count</th>
                                <th>Unique Pages</th>
                                <th>First Used</th>
                                <th>Last Used</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mediaHashes as $hash): ?>
                            <tr>
                                <td>
                                    <code style="font-size: 0.9rem;"><?php echo htmlspecialchars(substr($hash['ad_media_hash'], 0, 20)); ?>...</code>
                                </td>
                                <td><?php echo number_format($hash['usage_count']); ?></td>
                                <td><?php echo number_format($hash['unique_pages']); ?></td>
                                <td><?php echo $hash['first_used'] ? date('Y-m-d', strtotime($hash['first_used'])) : 'N/A'; ?></td>
                                <td><?php echo $hash['last_used'] ? date('Y-m-d', strtotime($hash['last_used'])) : 'N/A'; ?></td>
                                <td>
                                    <a href="media_hash_detail.php?hash=<?php echo urlencode($hash['ad_media_hash']); ?>" 
                                       class="btn" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php else: ?>
            <div class="card">
                <h3 class="card-title">No Data Available</h3>
                <p>No Ad Media Hash data found. Please import some CSV files first.</p>
                <a href="../index.php" class="btn">Go to Dashboard</a>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="../assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!empty($usageDistribution)): ?>
            // Create usage distribution pie chart
            const distributionData = <?php echo json_encode($usageDistribution); ?>;
            
            // Group small usage counts together
            const groupedData = [];
            let otherCount = 0;
            let otherTotal = 0;
            
            distributionData.forEach(item => {
                const usageCount = parseInt(item.usage_count);
                const hashCount = parseInt(item.hash_count);
                
                if (usageCount <= 5 || hashCount < 10) {
                    otherCount += hashCount;
                    otherTotal += usageCount * hashCount;
                } else {
                    groupedData.push({
                        label: `Used ${usageCount} time${usageCount > 1 ? 's' : ''}`,
                        value: hashCount,
                        percentage: 0 // Will calculate after grouping
                    });
                }
            });
            
            if (otherCount > 0) {
                groupedData.push({
                    label: 'Used ≤5 times or <10 hashes',
                    value: otherCount,
                    percentage: 0
                });
            }
            
            // Calculate percentages
            const total = groupedData.reduce((sum, item) => sum + item.value, 0);
            groupedData.forEach(item => {
                item.percentage = ((item.value / total) * 100).toFixed(1);
            });

            const pieData = {
                labels: groupedData.map(item => `${item.label} (${item.percentage}%)`),
                datasets: [{
                    data: groupedData.map(item => item.value),
                    backgroundColor: FacebookAdsAnalytics.generateColors(groupedData.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            };

            FacebookAdsAnalytics.createPieChart('distributionChart', pieData, {
                plugins: {
                    title: {
                        display: true,
                        text: 'Distribution of Media Hash Usage Frequency'
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

