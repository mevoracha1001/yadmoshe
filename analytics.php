<?php
// Yad Moshe - Analytics API

require_once 'config.php';

header('Content-Type: application/json');

// Get process ID
$processId = $_GET['processId'] ?? '';

if (empty($processId)) {
    echo json_encode(['error' => 'Process ID required']);
    exit;
}

$processFile = TEMP_DIR . $processId . '.json';

if (!file_exists($processFile)) {
    echo json_encode(['error' => 'Process not found']);
    exit;
}

$processData = json_decode(file_get_contents($processFile), true);

if (!$processData) {
    echo json_encode(['error' => 'Invalid process data']);
    exit;
}

// Generate analytics data
$analytics = generateAnalytics($processData);

echo json_encode($analytics);

function generateAnalytics($processData) {
    $analytics = [
        'campaignName' => $processData['campaignName'] ?? 'Unnamed Campaign',
        'startTime' => isset($processData['startTime']) ? date('Y-m-d H:i:s', $processData['startTime']) : '-',
        'endTime' => isset($processData['updated']) ? date('Y-m-d H:i:s', $processData['updated']) : '-',
        'duration' => calculateDuration($processData),
        'total' => $processData['total'] ?? 0,
        'sent' => $processData['sent'] ?? 0,
        'success' => $processData['success'] ?? 0,
        'failed' => $processData['failed'] ?? 0,
        'successRate' => 0,
        'timeLabels' => [],
        'successRates' => [],
        'hourLabels' => [],
        'messagesPerHour' => [],
        'errors' => []
    ];
    
    // Calculate success rate
    if ($analytics['sent'] > 0) {
        $analytics['successRate'] = round(($analytics['success'] / $analytics['sent']) * 100, 2);
    }
    
    // Generate time-based data
    if (isset($processData['startTime'])) {
        $startTime = $processData['startTime'];
        $endTime = $processData['updated'] ?? time();
        $duration = $endTime - $startTime;
        
        // Generate hourly data
        $hours = ceil($duration / 3600);
        for ($i = 0; $i < $hours; $i++) {
            $hourStart = $startTime + ($i * 3600);
            $analytics['hourLabels'][] = date('H:i', $hourStart);
            $analytics['messagesPerHour'][] = rand(10, 50); // Mock data
        }
        
        // Generate success rate over time
        $intervals = min(10, $hours);
        for ($i = 0; $i < $intervals; $i++) {
            $analytics['timeLabels'][] = date('H:i', $startTime + ($i * ($duration / $intervals)));
            $analytics['successRates'][] = rand(85, 98); // Mock data
        }
    }
    
    // Analyze errors from logs
    if (isset($processData['logs'])) {
        $analytics['errors'] = analyzeErrors($processData['logs']);
    }
    
    return $analytics;
}

function calculateDuration($processData) {
    if (!isset($processData['startTime'])) return '-';
    
    $startTime = $processData['startTime'];
    $endTime = $processData['updated'] ?? time();
    $duration = $endTime - $startTime;
    
    $hours = floor($duration / 3600);
    $minutes = floor(($duration % 3600) / 60);
    $seconds = $duration % 60;
    
    return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
}

function analyzeErrors($logs) {
    $errors = [];
    $errorCounts = [];
    
    $lines = explode("\n", $logs);
    foreach ($lines as $line) {
        if (strpos($line, 'FAILED') !== false) {
            // Extract error type
            if (preg_match('/FAILED: .* - (.*)/', $line, $matches)) {
                $errorType = $matches[1];
                if (strlen($errorType) > 50) {
                    $errorType = substr($errorType, 0, 50) . '...';
                }
                
                if (!isset($errorCounts[$errorType])) {
                    $errorCounts[$errorType] = 0;
                }
                $errorCounts[$errorType]++;
            }
        }
    }
    
    foreach ($errorCounts as $errorType => $count) {
        $errors[] = [
            'type' => $errorType,
            'count' => $count,
            'details' => 'Error occurred during message sending'
        ];
    }
    
    return $errors;
}
?>



