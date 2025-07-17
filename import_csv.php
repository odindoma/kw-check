<?php
/**
 * Console script for importing Facebook Ads CSV data
 * Usage: php import_csv.php
 * 
 * This script processes CSV files from the uploads/ directory and imports them into the database.
 * CSV files should be tab-separated and contain Facebook ads data.
 */

require_once 'includes/config.php';

// Function to extract base URL from full URL
function extractBaseUrl($fullUrl) {
    if (empty($fullUrl)) {
        return '';
    }
    
    // Parse the URL
    $parsed = parse_url($fullUrl);
    if (!$parsed || !isset($parsed['scheme']) || !isset($parsed['host'])) {
        return $fullUrl; // Return original if parsing fails
    }
    
    $baseUrl = $parsed['scheme'] . '://' . $parsed['host'];
    
    // Add path if it exists
    if (isset($parsed['path']) && $parsed['path'] !== '/') {
        $baseUrl .= $parsed['path'];
    }
    
    return $baseUrl;
}

// Function to parse Resource ID into Facebook Page ID and Ad ID
function parseResourceId($resourceId) {
    if (empty($resourceId)) {
        return ['', ''];
    }
    
    $parts = explode('/', $resourceId);
    if (count($parts) >= 2) {
        return [$parts[0], $parts[1]];
    }
    
    return [$resourceId, ''];
}

// Function to check if file was already processed
function isFileProcessed($filename) {
    $sql = "SELECT COUNT(*) FROM uploaded_files WHERE filename = ?";
    return getCount($sql, [$filename]) > 0;
}

// Function to save file record
function saveFileRecord($filename, $rowCount) {
    $sql = "INSERT INTO uploaded_files (filename, row_count) VALUES (?, ?)";
    executeQuery($sql, [$filename, $rowCount]);
}

// Function to import CSV data
function importCsvFile($filepath, $filename) {
    echo "Processing file: $filename\n";
    
    if (!file_exists($filepath)) {
        echo "Error: File not found: $filepath\n";
        return false;
    }
    
    if (isFileProcessed($filename)) {
        echo "File already processed: $filename\n";
        return true;
    }
    
    $handle = fopen($filepath, 'r');
    if (!$handle) {
        echo "Error: Cannot open file: $filepath\n";
        return false;
    }
    
    $rowCount = 0;
    $importedCount = 0;
    $header = null;
    
    while (($data = fgetcsv($handle, 0, "\t")) !== FALSE) {
        $rowCount++;
        
        // Skip header row
        if ($rowCount === 1) {
            $header = $data;
            continue;
        }
        
        // Skip empty rows
        if (empty($data) || count($data) < 11) {
            continue;
        }
        
        // Map CSV columns to variables
        $advertiser = isset($data[1]) ? trim($data[1]) : '';
        $resourceId = isset($data[2]) ? trim($data[2]) : '';
        $region = isset($data[3]) ? trim($data[3]) : '';
        $campaign = isset($data[4]) ? trim($data[4]) : '';
        $adTitle = isset($data[5]) ? trim($data[5]) : '';
        $adDescription = isset($data[6]) ? trim($data[6]) : '';
        $adMediaType = isset($data[7]) ? trim($data[7]) : '';
        $adMediaHash = isset($data[8]) ? trim($data[8]) : '';
        $targetUrlFull = isset($data[9]) ? trim($data[9]) : '';
        $firstShownAt = isset($data[10]) ? trim($data[10]) : '';
        $lastShownAt = isset($data[11]) ? trim($data[11]) : '';
        
        // Parse Resource ID
        list($facebookPageId, $adId) = parseResourceId($resourceId);
        
        // Extract base URL
        $targetUrlBase = extractBaseUrl($targetUrlFull);
        
        // Convert dates
        $firstShownAtFormatted = !empty($firstShownAt) ? date('Y-m-d H:i:s', strtotime($firstShownAt)) : null;
        $lastShownAtFormatted = !empty($lastShownAt) ? date('Y-m-d H:i:s', strtotime($lastShownAt)) : null;
        
        // Insert into database
        try {
            $sql = "INSERT INTO facebook_ads (
                advertiser, facebook_page_id, ad_id, region, campaign, 
                ad_title, ad_description, ad_media_type, ad_media_hash, 
                target_url_base, target_url_full, first_shown_at, last_shown_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            executeQuery($sql, [
                $advertiser, $facebookPageId, $adId, $region, $campaign,
                $adTitle, $adDescription, $adMediaType, $adMediaHash,
                $targetUrlBase, $targetUrlFull, $firstShownAtFormatted, $lastShownAtFormatted
            ]);
            
            $importedCount++;
        } catch (Exception $e) {
            echo "Error importing row $rowCount: " . $e->getMessage() . "\n";
        }
    }
    
    fclose($handle);
    
    // Save file record
    try {
        saveFileRecord($filename, $rowCount - 1); // Subtract 1 for header row
        echo "Successfully imported $importedCount records from $filename\n";
        return true;
    } catch (Exception $e) {
        echo "Error saving file record: " . $e->getMessage() . "\n";
        return false;
    }
}

// Main execution
echo "Facebook Ads CSV Import Script\n";
echo "==============================\n\n";

$uploadsDir = __DIR__ . '/uploads/';

if (!is_dir($uploadsDir)) {
    echo "Error: uploads directory not found: $uploadsDir\n";
    exit(1);
}

// Get all CSV files in uploads directory
$csvFiles = glob($uploadsDir . '*.csv');

if (empty($csvFiles)) {
    echo "No CSV files found in uploads directory.\n";
    exit(0);
}

echo "Found " . count($csvFiles) . " CSV file(s) to process.\n\n";

$totalProcessed = 0;
$totalSkipped = 0;

foreach ($csvFiles as $filepath) {
    $filename = basename($filepath);
    
    if (importCsvFile($filepath, $filename)) {
        $totalProcessed++;
    } else {
        $totalSkipped++;
    }
    
    echo "\n";
}

echo "Import completed!\n";
echo "Files processed: $totalProcessed\n";
echo "Files skipped: $totalSkipped\n";
?>

