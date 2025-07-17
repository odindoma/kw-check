<?php
require_once 'includes/config.php';

// Get basic statistics for dashboard
try {
    $totalAds = getCount("SELECT COUNT(*) FROM facebook_ads");
    $totalPageIds = getCount("SELECT COUNT(DISTINCT facebook_page_id) FROM facebook_ads WHERE facebook_page_id != ''");
    $totalTargetUrls = getCount("SELECT COUNT(DISTINCT target_url_base) FROM facebook_ads WHERE target_url_base != ''");
    $totalMediaHashes = getCount("SELECT COUNT(DISTINCT ad_media_hash) FROM facebook_ads WHERE ad_media_hash != ''");
    $totalFiles = getCount("SELECT COUNT(*) FROM uploaded_files");
} catch (Exception $e) {
    $totalAds = $totalPageIds = $totalTargetUrls = $totalMediaHashes = $totalFiles = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facebook Ads Analytics</title>
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <h1>Facebook Ads Analytics</h1>
            <p>Comprehensive analysis of Facebook advertising campaign data</p>
        </div>
    </header>

    <nav class="nav">
        <div class="container">
            <ul>
                <li><a href="index.php" class="active">Dashboard</a></li>
                <li><a href="pages/facebook_pages.php">Facebook Pages</a></li>
                <li><a href="pages/target_urls.php">Target URLs</a></li>
                <li><a href="pages/media_hashes.php">Ad Media Hashes</a></li>
            </ul>
        </div>
    </nav>

    <main class="container">
        <div class="main-content">
            <h2 class="page-title">Dashboard Overview</h2>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($totalAds); ?></div>
                    <div class="stat-label">Total Ads</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($totalPageIds); ?></div>
                    <div class="stat-label">Facebook Pages</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($totalTargetUrls); ?></div>
                    <div class="stat-label">Target URLs</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($totalMediaHashes); ?></div>
                    <div class="stat-label">Media Hashes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($totalFiles); ?></div>
                    <div class="stat-label">Imported Files</div>
                </div>
            </div>

            <div class="card">
                <h3 class="card-title">Quick Navigation</h3>
                <p>Use the navigation menu above to explore different aspects of your Facebook advertising data:</p>
                <ul style="margin: 1rem 0; padding-left: 2rem;">
                    <li><strong>Facebook Pages:</strong> View statistics for each Facebook Page ID, including usage counts and growth charts</li>
                    <li><strong>Target URLs:</strong> Analyze performance of different target URLs and their associated Facebook Pages</li>
                    <li><strong>Ad Media Hashes:</strong> Examine media usage patterns and distribution across campaigns</li>
                </ul>
            </div>

            <?php if ($totalFiles > 0): ?>
            <div class="card">
                <h3 class="card-title">Recent Import Activity</h3>
                <?php
                try {
                    $recentFiles = fetchAll("SELECT filename, row_count, upload_date FROM uploaded_files ORDER BY upload_date DESC LIMIT 5");
                    if (!empty($recentFiles)):
                ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Filename</th>
                                <th>Rows Imported</th>
                                <th>Upload Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentFiles as $file): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($file['filename']); ?></td>
                                <td><?php echo number_format($file['row_count']); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($file['upload_date'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php 
                    endif;
                } catch (Exception $e) {
                    echo "<p>Error loading recent files: " . htmlspecialchars($e->getMessage()) . "</p>";
                }
                ?>
            </div>
            <?php endif; ?>

            <div class="card">
                <h3 class="card-title">Data Import Instructions</h3>
                <p>To import new CSV data:</p>
                <ol style="margin: 1rem 0; padding-left: 2rem;">
                    <li>Place your CSV files in the <code>uploads/</code> directory</li>
                    <li>Ensure CSV files are tab-separated and follow the expected format</li>
                    <li>Run the import script: <code>php import_csv.php</code></li>
                    <li>The script will automatically process new files and avoid duplicates</li>
                </ol>
            </div>
        </div>
    </main>

    <script src="assets/js/main.js"></script>
</body>
</html>

