<?php
require_once '../../includes/config.php';

header('Content-Type: application/json');

$pageId = $_POST['page_id'] ?? '';
$targetUrl = $_POST['target_url'] ?? '';

if (empty($pageId) || empty($targetUrl)) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

try {
    $chartData = fetchAll("
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

    echo json_encode([
        'success' => true,
        'data' => $chartData
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

