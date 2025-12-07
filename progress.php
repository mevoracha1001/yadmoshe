<?php
// Yad Moshe - Progress Monitoring

require_once 'config.php';

header('Content-Type: application/json');

// Get the latest process file
$processFiles = glob(TEMP_DIR . '*.json');

if (empty($processFiles)) {
    echo json_encode([
        'total' => 0,
        'sent' => 0,
        'success' => 0,
        'failed' => 0,
        'status' => 'idle',
        'logs' => ''
    ]);
    exit;
}

// Get the most recent process file
$latestFile = '';
$latestTime = 0;

foreach ($processFiles as $file) {
    $mtime = filemtime($file);
    if ($mtime > $latestTime) {
        $latestTime = $mtime;
        $latestFile = $file;
    }
}

if (!file_exists($latestFile)) {
    echo json_encode([
        'total' => 0,
        'sent' => 0,
        'success' => 0,
        'failed' => 0,
        'status' => 'idle',
        'logs' => ''
    ]);
    exit;
}

$processData = json_decode(file_get_contents($latestFile), true);

if (!$processData) {
    echo json_encode([
        'total' => 0,
        'sent' => 0,
        'success' => 0,
        'failed' => 0,
        'status' => 'error',
        'logs' => 'Invalid process data'
    ]);
    exit;
}

echo json_encode([
    'total' => $processData['total'] ?? 0,
    'sent' => $processData['sent'] ?? 0,
    'success' => $processData['success'] ?? 0,
    'failed' => $processData['failed'] ?? 0,
    'status' => $processData['status'] ?? 'idle',
    'logs' => $processData['logs'] ?? '',
    'startTime' => $processData['startTime'] ?? null,
    'updated' => $processData['updated'] ?? null
]);



