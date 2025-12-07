<?php
// Error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// CRITICAL: Note - This endpoint returns JSON, so errors should be handled via JSON error handler
// The above settings will help with debugging, but the custom error handlers below will format errors as JSON

// Set JSON header immediately
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Error log file
$errorLogFile = __DIR__ . '/logs/php_errors.log';

// Ensure log directory exists
if (!file_exists(__DIR__ . '/logs/')) {
    mkdir(__DIR__ . '/logs/', 0755, true);
}

// Simple error handler that always returns JSON
function jsonError($message, $details = null) {
    global $errorLogFile;
    
    // Log the error with details
    $logEntry = date('Y-m-d H:i:s') . " - Error: " . $message;
    if ($details) {
        $logEntry .= " | Details: " . (is_string($details) ? $details : json_encode($details));
    }
    $logEntry .= "\n";
    file_put_contents($errorLogFile, $logEntry, FILE_APPEND);
    
    // Return JSON error (hide details in production, show in debug mode)
    $showDetails = isset($_GET['debug']) || isset($_POST['debug']);
    $errorMessage = $showDetails && $details ? $message . ' | ' . (is_string($details) ? $details : json_encode($details)) : $message;
    
    echo json_encode(['success' => false, 'error' => $errorMessage]);
    exit;
}

// Custom error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) use ($errorLogFile) {
    $errorTypes = [
        E_ERROR => 'E_ERROR',
        E_WARNING => 'E_WARNING',
        E_PARSE => 'E_PARSE',
        E_NOTICE => 'E_NOTICE',
        E_CORE_ERROR => 'E_CORE_ERROR',
        E_CORE_WARNING => 'E_CORE_WARNING',
        E_COMPILE_ERROR => 'E_COMPILE_ERROR',
        E_COMPILE_WARNING => 'E_COMPILE_WARNING',
        E_USER_ERROR => 'E_USER_ERROR',
        E_USER_WARNING => 'E_USER_WARNING',
        E_USER_NOTICE => 'E_USER_NOTICE',
        E_STRICT => 'E_STRICT',
        E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
        E_DEPRECATED => 'E_DEPRECATED',
        E_USER_DEPRECATED => 'E_USER_DEPRECATED'
    ];
    
    $errorType = $errorTypes[$errno] ?? 'UNKNOWN';
    $errorDetails = "$errorType: $errstr in $errfile on line $errline";
    
    // Log full error details
    $logEntry = date('Y-m-d H:i:s') . " - PHP Error: $errorDetails\n";
    file_put_contents($errorLogFile, $logEntry, FILE_APPEND);
    
    // Only show generic message unless in debug mode
    $showDetails = isset($_GET['debug']) || isset($_POST['debug']);
    jsonError('Server error occurred', $showDetails ? $errorDetails : null);
});

// Custom exception handler
set_exception_handler(function($exception) use ($errorLogFile) {
    $errorDetails = get_class($exception) . ': ' . $exception->getMessage() . ' in ' . $exception->getFile() . ' on line ' . $exception->getLine();
    $errorDetails .= "\nStack trace:\n" . $exception->getTraceAsString();
    
    // Log full exception details
    $logEntry = date('Y-m-d H:i:s') . " - Exception: $errorDetails\n";
    file_put_contents($errorLogFile, $logEntry, FILE_APPEND);
    
    // Only show generic message unless in debug mode
    $showDetails = isset($_GET['debug']) || isset($_POST['debug']);
    jsonError('Server error occurred', $showDetails ? $errorDetails : null);
});

// Load config only
require_once 'config.php';

// Start session
session_start();

// Check authentication
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    jsonError('Not authenticated');
}

// Get action
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'preview') {
    handlePreview();
} elseif ($action === 'send') {
    handleSend();
} elseif ($action === 'stop') {
    handleStop();
} elseif ($action === 'progress') {
    handleProgress();
} else {
    jsonError('Invalid action');
}

function handleImageUpload($imageFile, $isPreview = false) {
    // Validate image file
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    $fileExtension = strtolower(pathinfo($imageFile['name'], PATHINFO_EXTENSION));
    
    if (!in_array($imageFile['type'], $allowedTypes) && !in_array($fileExtension, $allowedExtensions)) {
        jsonError('Invalid image file type. Please upload JPEG, PNG, GIF, or WebP image.');
    }
    
    // Check file size (max 5MB for MMS)
    if ($imageFile['size'] > 5 * 1024 * 1024) {
        jsonError('Image file is too large. Maximum size is 5MB for MMS.');
    }
    
    // Generate unique filename
    $filename = uniqid('img_', true) . '_' . time() . '.' . $fileExtension;
    $targetPath = IMAGES_DIR . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($imageFile['tmp_name'], $targetPath)) {
        jsonError('Failed to upload image file.');
    }
    
    // Generate public URL
    $baseUrl = BASE_URL;
    if (substr($baseUrl, -1) !== '/') {
        $baseUrl .= '/';
    }
    $imageUrl = $baseUrl . 'uploads/images/' . $filename;
    
    return $imageUrl;
}

function handlePreview() {
    try {
        // Get form data
        $messageTemplate = trim($_POST['messageTemplate'] ?? '');
        $baseUrl = trim($_POST['baseUrl'] ?? '');
        
        // Check if file was uploaded
        if (!isset($_FILES['csvFile']) || $_FILES['csvFile']['error'] !== UPLOAD_ERR_OK) {
            $uploadError = $_FILES['csvFile']['error'] ?? 'No file uploaded';
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
            ];
            $errorMsg = $errorMessages[$uploadError] ?? "Upload error code: $uploadError";
            jsonError('Please select a CSV file to upload', $errorMsg);
        }
        
        if (empty($messageTemplate)) {
            jsonError('Please enter a message template');
        }
        
        $file = $_FILES['csvFile'];
        $filePath = $file['tmp_name'];
        
        // Validate file
        if (!file_exists($filePath) || !is_readable($filePath)) {
            jsonError('Uploaded file is not accessible', "File path: $filePath");
        }
        
        // Check file size (max 10MB)
        if ($file['size'] > 10 * 1024 * 1024) {
            jsonError('File is too large (max 10MB)', "File size: " . round($file['size'] / 1024 / 1024, 2) . " MB");
        }
        
        // Check file type
        $allowedTypes = ['text/csv', 'application/csv', 'application/vnd.ms-excel'];
        if (!in_array($file['type'], $allowedTypes) && !preg_match('/\.csv$/i', $file['name'])) {
            jsonError('Please upload a valid CSV file', "File type: " . ($file['type'] ?? 'unknown'));
        }
        
        // Handle image upload if provided
        $imageUrl = null;
        if (isset($_FILES['imageFile']) && $_FILES['imageFile']['error'] === UPLOAD_ERR_OK) {
            try {
                $imageUrl = handleImageUpload($_FILES['imageFile'], true); // true = preview mode
            } catch (Exception $e) {
                jsonError('Error uploading image: ' . $e->getMessage());
            }
        }
        
        // Read and parse CSV
        $contacts = readCSVFile($filePath);
        
        if (empty($contacts)) {
            jsonError('No valid contacts found in CSV. Please ensure your CSV has a "phone" column with phone numbers, or contains just phone numbers (one per row).');
        }
        
        // Generate preview
        $preview = generatePreview($contacts, $messageTemplate, $baseUrl, $imageUrl);
        
        // Return success
        echo json_encode([
            'success' => true,
            'preview' => $preview,
            'contactCount' => count($contacts)
        ]);
        
    } catch (Exception $e) {
        jsonError('Error processing preview: ' . $e->getMessage(), $e->getTraceAsString());
    } catch (Error $e) {
        jsonError('Fatal error processing preview: ' . $e->getMessage(), $e->getTraceAsString());
    }
}

function handleSend() {
    try {
        // Set unlimited execution time for SMS sending
        set_time_limit(0);
        ini_set('max_execution_time', 0);
        
        // Get form data
        $messageTemplate = trim($_POST['messageTemplate'] ?? '');
        $baseUrl = trim($_POST['baseUrl'] ?? '');
        $fromNumber = trim($_POST['fromNumber'] ?? '');
        $campaignName = trim($_POST['campaignName'] ?? '');
        $scheduleTime = $_POST['scheduleTime'] ?? '';
        $maxRetries = intval($_POST['maxRetries'] ?? 3);
        $retryDelay = intval($_POST['retryDelay'] ?? 5);
        
        // Validate required fields
        if (!isset($_FILES['csvFile']) || $_FILES['csvFile']['error'] !== UPLOAD_ERR_OK) {
            jsonError('Please select a CSV file to upload');
        }
        
        if (empty($messageTemplate)) {
            jsonError('Please enter a message template');
        }
        
        if (empty($fromNumber)) {
            jsonError('Please enter a Twilio phone number');
        }
        
        // Check if campaign is scheduled
        if (!empty($scheduleTime)) {
            $scheduledTime = strtotime($scheduleTime);
            if ($scheduledTime === false || $scheduledTime <= time()) {
                jsonError('Please select a valid future time for scheduling');
            }
            
            // Save scheduled campaign
            $scheduledCampaign = [
                'id' => uniqid('scheduled_', true),
                'campaignName' => $campaignName,
                'messageTemplate' => $messageTemplate,
                'baseUrl' => $baseUrl,
                'fromNumber' => $fromNumber,
                'scheduledTime' => $scheduledTime,
                'maxRetries' => $maxRetries,
                'retryDelay' => $retryDelay,
                'delayMin' => intval($_POST['delayMin'] ?? 1),
                'delayMax' => intval($_POST['delayMax'] ?? 3),
                'batchSize' => intval($_POST['batchSize'] ?? 50),
                'batchPause' => intval($_POST['batchPause'] ?? 2),
                'status' => 'scheduled',
                'created' => time()
            ];
            
            // Save CSV file for scheduled campaign
            $file = $_FILES['csvFile'];
            $filePath = $file['tmp_name'];
            $contacts = readCSVFile($filePath);
            $scheduledCampaign['contacts'] = $contacts;
            
            $scheduledFile = TEMP_DIR . 'scheduled_' . $scheduledCampaign['id'] . '.json';
            file_put_contents($scheduledFile, json_encode($scheduledCampaign));
            
            echo json_encode([
                'success' => true,
                'message' => 'Campaign scheduled successfully',
                'scheduledTime' => date('Y-m-d H:i:s', $scheduledTime),
                'campaignId' => $scheduledCampaign['id']
            ]);
            return;
        }
        
        $file = $_FILES['csvFile'];
        $filePath = $file['tmp_name'];
        
        // Validate file
        if (!file_exists($filePath) || !is_readable($filePath)) {
            jsonError('Uploaded file is not accessible');
        }
        
        // Read contacts
        $contacts = readCSVFile($filePath);
        
        if (empty($contacts)) {
            jsonError('No valid contacts found in CSV. Please ensure your CSV has a "phone" column with phone numbers, or contains just phone numbers (one per row).');
        }
        
        // Get Twilio credentials
        $twilio_sid = $_SESSION['twilio_sid'] ?? '';
        $twilio_token = $_SESSION['twilio_token'] ?? '';
        
        if (empty($twilio_sid) || empty($twilio_token)) {
            jsonError('Twilio credentials not found');
        }
        
        // Handle image upload if provided
        $imageUrl = null;
        if (isset($_FILES['imageFile']) && $_FILES['imageFile']['error'] === UPLOAD_ERR_OK) {
            $imageUrl = handleImageUpload($_FILES['imageFile'], false); // false = not preview mode
        }
        
        // Send SMS messages with real-time progress updates
        $result = sendBulkSMSWithProgress($contacts, $messageTemplate, $baseUrl, $fromNumber, $twilio_sid, $twilio_token, $campaignName, $maxRetries, $retryDelay, $imageUrl);
        
        echo json_encode($result);
    } catch (Exception $e) {
        jsonError('Error sending SMS: ' . $e->getMessage());
    }
}

function readCSVFile($filePath) {
    $contacts = [];
    
    try {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return $contacts;
        }
        
        // Read first row to determine format
        $firstRow = fgetcsv($handle, 1000, ',');
        if (!$firstRow || count($firstRow) < 1) {
            fclose($handle);
            return $contacts;
        }
        
        // Check if first row contains headers (non-numeric values)
        $hasHeaders = false;
        foreach ($firstRow as $cell) {
            $cell = trim($cell);
            // If any cell contains non-numeric characters (except +), assume it's a header
            if (!empty($cell) && !preg_match('/^[\d+\-\s\(\)]+$/', $cell)) {
                $hasHeaders = true;
                break;
            }
        }
        
        if ($hasHeaders) {
            // Original logic for CSV with headers
            $headers = array_map('strtolower', $firstRow);
            
            // Check if phone column exists
            if (!in_array('phone', $headers)) {
                fclose($handle);
                return $contacts;
            }
            
            // Read data rows
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                if (count($data) === count($headers)) {
                    $contact = array_combine($headers, $data);
                    
                    // Process phone number
                    if (!empty($contact['phone'])) {
                        $phone = trim($contact['phone']);
                        $phone = preg_replace('/[^\d+]/', '', $phone);
                        
                        // Add + prefix if missing
                        if (!preg_match('/^\+/', $phone)) {
                            $phone = '+' . $phone;
                        }
                        
                        // Basic phone validation (must have at least 10 digits)
                        if (strlen(preg_replace('/[^\d]/', '', $phone)) >= 10) {
                            $contact['phone'] = $phone;
                            $contacts[] = $contact;
                        }
                    }
                }
                
                // Limit to prevent memory issues
                if (count($contacts) >= 10000) {
                    break;
                }
            }
        } else {
            // New logic for CSV with just phone numbers (no headers)
            // Process the first row as a phone number
            $phone = trim($firstRow[0]);
            $phone = preg_replace('/[^\d+]/', '', $phone);
            
            // Add + prefix if missing
            if (!preg_match('/^\+/', $phone)) {
                $phone = '+' . $phone;
            }
            
            // Basic phone validation (must have at least 10 digits)
            if (strlen(preg_replace('/[^\d]/', '', $phone)) >= 10) {
                $contacts[] = ['phone' => $phone];
            }
            
            // Read remaining rows
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                if (!empty($data[0])) {
                    $phone = trim($data[0]);
                    $phone = preg_replace('/[^\d+]/', '', $phone);
                    
                    // Add + prefix if missing
                    if (!preg_match('/^\+/', $phone)) {
                        $phone = '+' . $phone;
                    }
                    
                    // Basic phone validation (must have at least 10 digits)
                    if (strlen(preg_replace('/[^\d]/', '', $phone)) >= 10) {
                        $contacts[] = ['phone' => $phone];
                    }
                }
                
                // Limit to prevent memory issues
                if (count($contacts) >= 10000) {
                    break;
                }
            }
        }
        
        fclose($handle);
        
    } catch (Exception $e) {
        return [];
    }
    
    return $contacts;
}

function generatePreview($contacts, $template, $baseUrl, $imageUrl = null) {
    $preview = '<div style="background: #f8fafc; border-radius: 12px; padding: 1.5rem; margin-bottom: 1rem;">';
    $preview .= '<h3 style="color: #1e40af; margin-bottom: 1.5rem; font-size: 1.25rem; font-weight: 600; border-bottom: 2px solid #e2e8f0; padding-bottom: 0.5rem;">';
    $preview .= 'Sample Messages (' . min(3, count($contacts)) . ' of ' . count($contacts) . ' contacts)';
    $preview .= '</h3>';
    
    if ($imageUrl) {
        $preview .= '<div style="margin-bottom: 1.5rem; padding: 1rem; background: #e0f2fe; border-radius: 8px; border: 1px solid #7dd3fc;">';
        $preview .= '<div style="font-weight: 600; color: #0c4a6e; margin-bottom: 0.5rem;">📷 Image will be sent with each message:</div>';
        $preview .= '<img src="' . htmlspecialchars($imageUrl) . '" alt="Preview" style="max-width: 300px; max-height: 200px; border-radius: 8px; border: 1px solid #7dd3fc; display: block;">';
        $preview .= '<div style="margin-top: 0.5rem; font-size: 0.875rem; color: #075985;">Each recipient will receive a separate MMS message with this image.</div>';
        $preview .= '</div>';
    }
    
    $count = 0;
    foreach ($contacts as $contact) {
        if ($count >= 3) break;
        
        $message = replacePlaceholders($template, $contact, $baseUrl);
        
        $preview .= '<div style="margin-bottom: 1.5rem; padding: 1.25rem; background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);">';
        
        $preview .= '<div style="margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid #e2e8f0;">';
        $preview .= '<div style="font-weight: 600; color: #1e293b; margin-bottom: 0.25rem; font-size: 0.95rem;">Recipient Details:</div>';
        $preview .= '<div style="font-size: 0.875rem; color: #64748b;">';
        $preview .= '<strong>Phone:</strong> ' . htmlspecialchars($contact['phone'] ?? 'N/A');
        if (!empty($contact['name'])) {
            $preview .= '<br><strong>Name:</strong> ' . htmlspecialchars($contact['name']);
        }
        if (!empty($contact['id'])) {
            $preview .= '<br><strong>ID:</strong> ' . htmlspecialchars($contact['id']);
        }
        $preview .= '</div>';
        $preview .= '</div>';
        
        $preview .= '<div style="color: #374151; line-height: 1.6;">';
        $preview .= '<div style="font-weight: 600; color: #1e293b; margin-bottom: 0.5rem; font-size: 0.95rem;">Message Content:</div>';
        $preview .= '<div style="background: #f8fafc; padding: 1rem; border-radius: 6px; border: 1px solid #e2e8f0; font-family: \'Inter\', sans-serif;">';
        
        // Show image preview in message if available
        if ($imageUrl) {
            $preview .= '<div style="margin-bottom: 0.75rem; padding: 0.5rem; background: #ffffff; border-radius: 4px; border: 1px solid #cbd5e1;">';
            $preview .= '<div style="font-size: 0.75rem; color: #64748b; margin-bottom: 0.25rem;">📷 Image attachment:</div>';
            $preview .= '<img src="' . htmlspecialchars($imageUrl) . '" alt="Message image" style="max-width: 200px; max-height: 150px; border-radius: 4px; display: block;">';
            $preview .= '</div>';
        }
        
        $preview .= nl2br(htmlspecialchars($message));
        $preview .= '</div>';
        $preview .= '</div>';
        
        $preview .= '</div>';
        
        $count++;
    }
    
    $preview .= '</div>';
    
    return $preview;
}

function replacePlaceholders($template, $contact, $baseUrl) {
    $replacements = [
        '@name' => $contact['name'] ?? '',
        '@id' => $contact['id'] ?? '',
        '@phone' => $contact['phone'] ?? '',
        '@link' => $baseUrl . ($contact['id'] ?? '')
    ];
    
    $message = str_replace(array_keys($replacements), array_values($replacements), $template);
    
    // Automatically add STOP text if not already present
    if (stripos($message, 'stop') === false && stripos($message, 'unsubscribe') === false) {
        $message .= "\n\nReply STOP to stop";
    }
    
    return $message;
}

function handleStop() {
    try {
        $processId = $_POST['processId'] ?? $_GET['processId'] ?? '';
        
        if (empty($processId)) {
            jsonError('Process ID required');
        }
        
        $processFile = TEMP_DIR . $processId . '.json';
        
        if (!file_exists($processFile)) {
            jsonError('Process not found');
        }
        
        $processData = json_decode(file_get_contents($processFile), true);
        if (!$processData) {
            jsonError('Invalid process data');
        }
        
        // Update process status to stopped
        $processData['status'] = 'stopped';
        $processData['logs'] .= "\n[" . date('Y-m-d H:i:s') . "] Campaign stopped by user\n";
        $processData['updated'] = time();
        
        file_put_contents($processFile, json_encode($processData));
        
        echo json_encode([
            'success' => true,
            'message' => 'Campaign stop command sent'
        ]);
        
    } catch (Exception $e) {
        jsonError('Error stopping campaign: ' . $e->getMessage());
    }
}

function handleProgress() {
    try {
        $processId = $_POST['processId'] ?? $_GET['processId'] ?? '';
        
        if (empty($processId)) {
            jsonError('Process ID required');
        }
        
        $processFile = TEMP_DIR . $processId . '.json';
        
        if (!file_exists($processFile)) {
            jsonError('Process not found');
        }
        
        $processData = json_decode(file_get_contents($processFile), true);
        if (!$processData) {
            jsonError('Invalid process data');
        }
        
        echo json_encode([
            'success' => true,
            'processId' => $processId,
            'total' => $processData['total'] ?? 0,
            'sent' => $processData['sent'] ?? 0,
            'successCount' => $processData['success'] ?? 0,
            'failed' => $processData['failed'] ?? 0,
            'status' => $processData['status'] ?? 'idle',
            'logs' => $processData['logs'] ?? '',
            'startTime' => $processData['startTime'] ?? null,
            'updated' => $processData['updated'] ?? null,
            'campaignName' => $processData['campaignName'] ?? 'Unnamed Campaign'
        ]);
        
    } catch (Exception $e) {
        jsonError('Error getting progress: ' . $e->getMessage());
    }
}
function sendBulkSMS($contacts, $messageTemplate, $baseUrl, $fromNumber, $twilioSid, $twilioToken, $campaignName = '', $maxRetries = 3, $retryDelay = 5) {
    $totalContacts = count($contacts);
    $sent = 0;
    $success = 0;
    $failed = 0;
    $logs = '';
    
    // Get configuration from form or use defaults
    $delayMin = max(1, intval($_POST['delayMin'] ?? MIN_DELAY_SECONDS));
    $delayMax = max($delayMin, intval($_POST['delayMax'] ?? MAX_DELAY_SECONDS));
    $batchSize = max(10, intval($_POST['batchSize'] ?? MIN_BATCH_SIZE));
    $batchPause = max(1, intval($_POST['batchPause'] ?? MIN_BATCH_PAUSE_MINUTES));
    
    // Create process ID for tracking
    $processId = uniqid('sms_', true);
    
    // Initialize progress tracking
    $progressData = [
        'id' => $processId,
        'campaignName' => $campaignName ?: 'Unnamed Campaign',
        'total' => $totalContacts,
        'sent' => 0,
        'success' => 0,
        'failed' => 0,
        'status' => 'running',
        'logs' => 'Starting SMS campaign...',
        'startTime' => time(),
        'delayMin' => $delayMin,
        'delayMax' => $delayMax,
        'batchSize' => $batchSize,
        'batchPause' => $batchPause,
        'maxRetries' => $maxRetries,
        'retryDelay' => $retryDelay,
        'retryQueue' => []
    ];
    
    // Save initial progress
    file_put_contents(TEMP_DIR . $processId . '.json', json_encode($progressData));
    
    $batchCount = 0;
    $currentBatchSize = $batchSize;
    
    foreach ($contacts as $index => $contact) {
        // Check if campaign was stopped
        if (file_exists(TEMP_DIR . $processId . '.json')) {
            $currentData = json_decode(file_get_contents(TEMP_DIR . $processId . '.json'), true);
            if ($currentData && $currentData['status'] === 'stopped') {
                $logs .= "[" . date('Y-m-d H:i:s') . "] Campaign stopped by user\n";
                break;
            }
        }
        
        // Update progress before sending
        $progressData['sent'] = $sent;
        $progressData['success'] = $success;
        $progressData['failed'] = $failed;
        $progressData['logs'] = $logs;
        file_put_contents(TEMP_DIR . $processId . '.json', json_encode($progressData));
        
        // Replace placeholders
        $message = replacePlaceholders($messageTemplate, $contact, $baseUrl);
        
        // Send SMS (imageUrl would need to be passed to this function if using old sendBulkSMS)
        $result = sendTwilioSMS($twilioSid, $twilioToken, $fromNumber, $contact['phone'], $message);
        
        $timestamp = date('Y-m-d H:i:s');
        $sent++;
        
        if ($result['success']) {
            $success++;
            $logEntry = "[$timestamp] SUCCESS: {$contact['phone']} - {$result['sid']}\n";
        } else {
            $failed++;
            $logEntry = "[$timestamp] FAILED: {$contact['phone']} - {$result['error']}\n";
        }
        
        $logs .= $logEntry;
        
        // Check if we need to pause for batch
        $batchCount++;
        if ($batchCount >= $currentBatchSize) {
            $logs .= "[$timestamp] Batch complete. Pausing for $batchPause minutes...\n";
            
            // Update progress during pause
            $progressData['sent'] = $sent;
            $progressData['success'] = $success;
            $progressData['failed'] = $failed;
            $progressData['logs'] = $logs;
            file_put_contents(TEMP_DIR . $processId . '.json', json_encode($progressData));
            
            // Do not pause — immediately continue. Check once if campaign was stopped.
            if (file_exists(TEMP_DIR . $processId . '.json')) {
                $currentData = json_decode(file_get_contents(TEMP_DIR . $processId . '.json'), true);
                if ($currentData && $currentData['status'] === 'stopped') {
                    $logs .= "[" . date('Y-m-d H:i:s') . "] Campaign stopped during pause\n";
                    break;
                }
            }

            $batchCount = 0;
            $currentBatchSize = $batchSize;
            $logs .= "[" . date('Y-m-d H:i:s') . "] Resuming with batch size: $currentBatchSize\n";
        } else {
            // No per-message delay — send as fast as possible
        }
        
        // Prevent script timeout by flushing output
        if (ob_get_level()) {
            ob_flush();
        }
        flush();
    }
    
    // Final update
    $finalStatus = 'completed';
    if (file_exists(TEMP_DIR . $processId . '.json')) {
        $currentData = json_decode(file_get_contents(TEMP_DIR . $processId . '.json'), true);
        if ($currentData && $currentData['status'] === 'stopped') {
            $finalStatus = 'stopped';
        }
    }
    
    $logs .= "[" . date('Y-m-d H:i:s') . "] Campaign $finalStatus!\n";
    $progressData['sent'] = $sent;
    $progressData['success'] = $success;
    $progressData['failed'] = $failed;
    $progressData['logs'] = $logs;
    $progressData['status'] = $finalStatus;
    file_put_contents(TEMP_DIR . $processId . '.json', json_encode($progressData));
    
    // Save final log to file
    $logFile = LOG_DIR . 'sms_campaign_' . date('Y-m-d_H-i-s') . '.log';
    file_put_contents($logFile, $logs);
    
    return [
        'success' => ($success > 0 || $finalStatus === 'stopped'),
        'processId' => $processId,
        'total' => $totalContacts,
        'sent' => $sent,
        'successCount' => $success,
        'failed' => $failed,
        'error' => ($success === 0 && $finalStatus === 'completed' ? 'All messages failed to send. Check logs for details.' : null)
    ];
}

function sendBulkSMSWithProgress($contacts, $messageTemplate, $baseUrl, $fromNumber, $twilioSid, $twilioToken, $campaignName = '', $maxRetries = 3, $retryDelay = 5, $imageUrl = null) {
    // Set unlimited execution time for SMS sending
    set_time_limit(0);
    ini_set('max_execution_time', 0);
    
    $totalContacts = count($contacts);
    $sent = 0;
    $success = 0;
    $failed = 0;
    $logs = '';
    
    // Get configuration from form or use defaults
    $batchSize = max(10, intval($_POST['batchSize'] ?? 100));
    $maxConcurrent = max(1, min(50, intval($_POST['maxConcurrent'] ?? 25)));
    
    // Create process ID for tracking
    $processId = uniqid('sms_', true);
    
    // Initialize progress tracking
    $progressData = [
        'id' => $processId,
        'campaignName' => $campaignName ?: 'Unnamed Campaign',
        'total' => $totalContacts,
        'sent' => 0,
        'success' => 0,
        'failed' => 0,
        'status' => 'running',
        'logs' => 'Starting SMS campaign...',
        'startTime' => time(),
        'batchSize' => $batchSize,
        'maxConcurrent' => $maxConcurrent,
        'maxRetries' => $maxRetries,
        'retryDelay' => $retryDelay,
        'retryQueue' => []
    ];
    
    // Save initial progress
    file_put_contents(TEMP_DIR . $processId . '.json', json_encode($progressData));
    
    // Set headers for streaming
    header('Content-Type: text/plain');
    header('Cache-Control: no-cache');
    
    // Flush output buffer to enable real-time updates
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Process contacts in batches with concurrent requests
    $batches = array_chunk($contacts, $batchSize);
    $batchNumber = 0;
    
    foreach ($batches as $batch) {
        $batchNumber++;
        $batchStartTime = microtime(true);
        
        echo "[" . date('Y-m-d H:i:s') . "] Processing batch $batchNumber/" . count($batches) . " ({$maxConcurrent} concurrent requests)...\n";
        flush();
        
        // Process batch with concurrent requests
        $batchResults = sendConcurrentSMS($batch, $messageTemplate, $baseUrl, $fromNumber, $twilioSid, $twilioToken, $maxConcurrent, $imageUrl);
        
        // Process results
        foreach ($batchResults as $result) {
            $sent++;
            $timestamp = date('Y-m-d H:i:s');
            
            if ($result['success']) {
                $success++;
                $logEntry = "[$timestamp] SUCCESS: {$result['phone']} - {$result['sid']}\n";
                echo "[$timestamp] SUCCESS: {$result['phone']}\n";
            } else {
                $failed++;
                $logEntry = "[$timestamp] FAILED: {$result['phone']} - {$result['error']}\n";
                echo "[$timestamp] FAILED: {$result['phone']} - {$result['error']}\n";
            }
            
            $logs .= $logEntry;
        }
        
        // Update progress
        $progressData['sent'] = $sent;
        $progressData['success'] = $success;
        $progressData['failed'] = $failed;
        $progressData['logs'] = $logs;
        $progressData['updated'] = time();
        file_put_contents(TEMP_DIR . $processId . '.json', json_encode($progressData));
        
        // Report batch progress
        $batchTime = round(microtime(true) - $batchStartTime, 2);
        $batchRate = count($batch) / $batchTime;
        echo "Progress: $sent/$totalContacts (Success: $success, Failed: $failed)\n";
        echo "Batch $batchNumber completed in {$batchTime}s ({$batchRate} msg/sec)\n";
        echo "---\n";
        
        // Flush output immediately
        if (ob_get_level()) {
            ob_flush();
        }
        flush();
    }
    
    // Final update
    $finalStatus = 'completed';
    $logs .= "[" . date('Y-m-d H:i:s') . "] Campaign completed!\n";
    $progressData['sent'] = $sent;
    $progressData['success'] = $success;
    $progressData['failed'] = $failed;
    $progressData['logs'] = $logs;
    $progressData['status'] = $finalStatus;
    $progressData['updated'] = time();
    file_put_contents(TEMP_DIR . $processId . '.json', json_encode($progressData));
    
    // Send final update to console
    $finalTimestamp = date('Y-m-d H:i:s');
    echo "\n";
    echo "========================================\n";
    echo "[$finalTimestamp] CAMPAIGN COMPLETED!\n";
    echo "========================================\n";
    echo "Total Contacts: $totalContacts\n";
    echo "Messages Sent: $sent\n";
    echo "Successful: $success\n";
    echo "Failed: $failed\n";
    echo "Success Rate: " . round(($success / $sent) * 100, 2) . "%\n";
    echo "========================================\n";
    
    if (ob_get_level()) {
        ob_flush();
    }
    flush();
    
    // Save final log to file
    $logFile = LOG_DIR . 'sms_campaign_' . date('Y-m-d_H-i-s') . '.log';
    file_put_contents($logFile, $logs);
    
    return [
        'success' => ($success > 0),
        'processId' => $processId,
        'total' => $totalContacts,
        'sent' => $sent,
        'successCount' => $success,
        'failed' => $failed,
        'error' => ($success === 0 ? 'All messages failed to send. Check logs for details.' : null)
    ];
}

function sendConcurrentSMS($contacts, $messageTemplate, $baseUrl, $fromNumber, $twilioSid, $twilioToken, $maxConcurrent = 25, $imageUrl = null) {
    $results = [];
    $mh = curl_multi_init();
    $handles = [];
    $contactMap = [];
    
    // Ensure SID is valid
    $twilioSid = trim($twilioSid);
    if (strpos($twilioSid, 'AC') !== 0) {
        // Return all as failed
        foreach ($contacts as $contact) {
            $results[] = [
                'success' => false,
                'phone' => $contact['phone'],
                'error' => 'Invalid Twilio SID',
                'sid' => null
            ];
        }
        return $results;
    }
    
    // Process contacts in chunks based on maxConcurrent
    $chunks = array_chunk($contacts, $maxConcurrent);
    
    foreach ($chunks as $chunk) {
        $handles = [];
        $contactMap = [];
        
        // Create cURL handles for each contact in the chunk
        foreach ($chunk as $contact) {
            $message = replacePlaceholders($messageTemplate, $contact, $baseUrl);
            
            $data = [
                'From' => $fromNumber,
                'To' => $contact['phone'],
                'Body' => $message
            ];
            
            // Add image URL for MMS if provided
            if (!empty($imageUrl)) {
                $data['MediaUrl'] = $imageUrl;
            }
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://api.twilio.com/2010-04-01/Accounts/$twilioSid/Messages.json");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, "$twilioSid:$twilioToken");
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            curl_multi_add_handle($mh, $ch);
            $handles[] = $ch;
            $contactMap[(int)$ch] = $contact;
        }
        
        // Execute all handles concurrently
        $running = null;
        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh);
        } while ($running > 0);
        
        // Collect results
        foreach ($handles as $ch) {
            $response = curl_multi_getcontent($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            $contact = $contactMap[(int)$ch];
            
            if ($curlError) {
                $results[] = [
                    'success' => false,
                    'phone' => $contact['phone'],
                    'error' => 'cURL Error: ' . $curlError,
                    'sid' => null
                ];
            } elseif ($httpCode !== 201) {
                $errorData = json_decode($response, true);
                $errorMessage = isset($errorData['message']) ? $errorData['message'] : 'Unknown error';
                $errorCode = isset($errorData['code']) ? $errorData['code'] : '';
                $errorDetails = "HTTP $httpCode: $errorMessage";
                if ($errorCode) {
                    $errorDetails .= " (Code: $errorCode)";
                }
                $results[] = [
                    'success' => false,
                    'phone' => $contact['phone'],
                    'error' => $errorDetails,
                    'sid' => null
                ];
            } else {
                $responseData = json_decode($response, true);
                if ($responseData && isset($responseData['sid'])) {
                    $results[] = [
                        'success' => true,
                        'phone' => $contact['phone'],
                        'sid' => $responseData['sid'],
                        'error' => null
                    ];
                } else {
                    $results[] = [
                        'success' => false,
                        'phone' => $contact['phone'],
                        'error' => 'Invalid response from Twilio',
                        'sid' => null
                    ];
                }
            }
            
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
    }
    
    curl_multi_close($mh);
    
    return $results;
}

function sendTwilioSMS($sid, $token, $from, $to, $message, $imageUrl = null) {
    try {
        // Prepare the data for the POST request
        $data = [
            'From' => $from,
            'To' => $to,
            'Body' => $message
        ];
        
        // Add image URL for MMS if provided
        if (!empty($imageUrl)) {
            $data['MediaUrl'] = $imageUrl;
        }
        
        // Initialize cURL
        $ch = curl_init();
        
        // Set cURL options
        // Ensure SID is trimmed and starts with 'AC'
        $sid = trim($sid);
        if (strpos($sid, 'AC') !== 0) {
            return [
                'success' => false,
                'error' => 'Twilio SID must start with "AC". Current SID: ' . $sid,
                'sid' => null
            ];
        }
    curl_setopt($ch, CURLOPT_URL, "https://api.twilio.com/2010-04-01/Accounts/$sid/Messages.json");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, "$sid:$token");
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        // Execute the request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        
        curl_close($ch);
        
        // Check for cURL errors
        if ($curlError) {
            return [
                'success' => false,
                'error' => 'cURL Error: ' . $curlError,
                'sid' => null
            ];
        }
        
        // Check HTTP response code
        if ($httpCode !== 201) {
            $errorData = json_decode($response, true);
            $errorMessage = isset($errorData['message']) ? $errorData['message'] : 'Unknown error';
            $errorCode = isset($errorData['code']) ? $errorData['code'] : '';
            $errorMoreInfo = isset($errorData['more_info']) ? $errorData['more_info'] : '';
            $errorDetails = "HTTP $httpCode: $errorMessage";
            if ($errorCode) {
                $errorDetails .= " (Code: $errorCode)";
            }
            if ($errorMoreInfo) {
                $errorDetails .= " More info: $errorMoreInfo";
            }
            $errorDetails .= " | Raw: " . $response;
            return [
                'success' => false,
                'error' => $errorDetails,
                'sid' => null
            ];
        }
        
        // Parse successful response
        $responseData = json_decode($response, true);
        
        if (!$responseData || !isset($responseData['sid'])) {
            return [
                'success' => false,
                'error' => 'Invalid response from Twilio',
                'sid' => null
            ];
        }
        
        return [
            'success' => true,
            'sid' => $responseData['sid'],
            'error' => null
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Exception: ' . $e->getMessage(),
            'sid' => null
        ];
    }
}

function startSendProcess($contacts, $messageTemplate, $baseUrl, $fromNumber, $twilioSid, $twilioToken, $campaignName = '', $maxRetries = 3, $retryDelay = 5) {
    // Generate unique process ID
    $processId = uniqid('sms_', true);
    
    // Get configuration from form or use defaults
    $delayMin = max(1, intval($_POST['delayMin'] ?? MIN_DELAY_SECONDS));
    $delayMax = max($delayMin, intval($_POST['delayMax'] ?? MAX_DELAY_SECONDS));
    $batchSize = max(10, intval($_POST['batchSize'] ?? MIN_BATCH_SIZE));
    $batchPause = max(1, intval($_POST['batchPause'] ?? MIN_BATCH_PAUSE_MINUTES));
    
    // Prepare process data (matching what send_sms.php expects)
    $processData = [
        'id' => $processId,
        'campaignName' => $campaignName ?: 'Unnamed Campaign',
        'contacts' => $contacts,
        'messageTemplate' => $messageTemplate,
        'baseUrl' => $baseUrl,
        'fromNumbers' => [$fromNumber], // Array of phone numbers
        'currentNumberIndex' => 0,
        'batchSize' => $batchSize,
        'delayMin' => $delayMin,
        'delayMax' => $delayMax,
        'batchPause' => $batchPause,
        'maxRetries' => $maxRetries,
        'retryDelay' => $retryDelay,
        'twilio_sid' => $twilioSid,
        'twilio_token' => $twilioToken,
        'total' => count($contacts),
        'sent' => 0,
        'success' => 0,
        'failed' => 0,
        'status' => 'starting',
        'logs' => 'Starting SMS campaign...',
        'startTime' => time()
    ];
    
    // Save to temp file
    $tempFile = TEMP_DIR . $processId . '.json';
    if (!file_put_contents($tempFile, json_encode($processData))) {
        throw new Exception('Failed to save process data');
    }
    
    // Start background process
    if (file_exists(__DIR__ . '/send_sms.php')) {
        // Use different approach for Windows
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Use wscript to run in background on Windows
            $vbsScript = __DIR__ . '/run_sms.vbs';
            $vbsContent = 'Set objShell = CreateObject("WScript.Shell")\n';
            $vbsContent .= 'objShell.Run "php \"' . __DIR__ . '/send_sms.php\" \"' . $processId . '\"", 0, False\n';
            file_put_contents($vbsScript, $vbsContent);
            
            // Run the VBS script
            exec('cscript //NoLogo "' . $vbsScript . '"');
            
            // Clean up the VBS script
            unlink($vbsScript);
        } else {
            // Unix/Linux approach
            $command = "php \"" . __DIR__ . "/send_sms.php\" \"$processId\" > /dev/null 2>&1 &";
            exec($command);
        }
    } else {
        throw new Exception('send_sms.php file not found');
    }
    
    return $processId;
}

?>