<?php
require_once '../../includes/config.php';

header('Content-Type: application/json');

$mediaHash = $_POST['media_hash'] ?? '';

if (empty($mediaHash)) {
    echo json_encode(['success' => false, 'error' => 'Missing media hash parameter']);
    exit;
}

try {
    // Get Facebook Pages using this media hash
    $pages = fetchAll("
        SELECT 
            facebook_page_id,
            COUNT(*) as usage_count
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

    echo json_encode([
        'success' => true,
        'pages' => $pages,
        'timeline' => $timeline
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

