<?php
// Error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Yad Moshe - Professional SMS Campaign Management System
// Main application file

// Configuration - Load config BEFORE starting session
require_once 'config.php';

session_start();

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Check authentication
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    // Handle login
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (!empty($username) && !empty($password)) {
            $_SESSION['authenticated'] = true;
            $_SESSION['twilio_sid'] = $username;
            $_SESSION['twilio_token'] = $password;
        } else {
            $error = 'Please enter your Twilio SID and Auth Token';
        }
    }
    
    // Show login form
    if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Yad Moshe - SMS Campaign Management</title>
            <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
            <style>
                :root {
                    --primary: #3682de;
                    --primary-hover: #2b6fc7;
                    --primary-light: rgba(54, 130, 222, 0.08);
                    --secondary: #868e96;
                    --success: #00c853;
                    --danger: #ff1744;
                    --warning: #ff9800;
                    --info: #2196f3;
                    --bg: #f8f8f8;
                    --surface: #ffffff;
                    --card-bg: #ffffff;
                    --text: #000000;
                    --text-light: #6c757d;
                    --text-muted: #adb5bd;
                    --border: #e0e0e0;
                    --border-light: #f0f0f0;
            --shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            --shadow-hover: 0 2px 8px rgba(0, 0, 0, 0.08);
            --shadow-card: 0 1px 4px rgba(0, 0, 0, 0.04);
                    --radius: 16px;
                    --radius-sm: 12px;
                    --radius-lg: 20px;
                }
                
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                body { 
                    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    background: var(--bg);
                    min-height: 100vh;
                    color: var(--text);
                    line-height: 1.6;
                    -webkit-font-smoothing: antialiased;
                    -moz-osx-font-smoothing: grayscale;
                }
                
                .login-container {
                    background: var(--card-bg);
                    padding: 2rem;
                    border-radius: 4px;
                    box-shadow: var(--shadow);
                    width: 100%;
                    max-width: 400px;
                    margin: 4rem auto;
                    border: 1px solid var(--border);
                }
                
                .brand-header {
                    text-align: center;
                    margin-bottom: 2rem;
                }
                
                .brand-header h1 {
                    color: var(--primary);
                    font-size: 2.5rem;
                    font-weight: 700;
                    margin-bottom: 0.5rem;
                    letter-spacing: -0.025em;
                }
                
                .brand-header p {
                    color: var(--text-light);
                    font-size: 1.125rem;
                    font-weight: 400;
                }
                
                .form-group {
                    margin-bottom: 1.75rem;
                }
                
                label {
                    display: block;
                    margin-bottom: 0.75rem;
                    font-weight: 600;
                    color: var(--text);
                    font-size: 0.95rem;
                    letter-spacing: 0.025em;
                    text-transform: uppercase;
                }
                
                input[type="text"], input[type="password"] {
                    width: 100%;
                    padding: 0.75rem;
                    border: 1px solid var(--border);
                    border-radius: 4px;
                    font-size: 1rem;
                    background: #ffffff;
                }
                
                input[type="text"]:focus, input[type="password"]:focus {
                    outline: none;
                    border-color: var(--primary);
                    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
                }
                
                .btn {
                    width: 100%;
                    padding: 0.75rem 1rem;
                    background: var(--primary);
                    color: white;
                    border: none;
                    border-radius: 4px;
                    font-size: 1rem;
                    font-weight: 500;
                    cursor: pointer;
                }
                
                .btn:hover {
                    background: var(--primary-hover);
                }
                
                .error {
                    background: #fef2f2;
                    color: var(--danger);
                    padding: 1rem;
                    border-radius: 4px;
                    margin-bottom: 1.5rem;
                    border: 1px solid #fecaca;
                    border-left: 4px solid var(--danger);
                    font-weight: 500;
                }
                
                .security-notice {
                    background: #fefce8;
                    color: #92400e;
                    padding: 1rem;
                    border-radius: 4px;
                    margin-top: 1.5rem;
                    border: 1px solid #fde047;
                    font-size: 0.875rem;
                }
            </style>
        </head>
        <body>
            <div class="login-container">
                <div class="brand-header">
                    <h1>Yad Moshe</h1>
                    <p>SMS Campaign Management System</p>
                </div>
                
                <?php if (isset($error)): ?>
                    <div class="error">
                        <strong>Authentication Required</strong><br>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="username">Twilio Account SID</label>
                        <input type="text" id="username" name="username" required placeholder="Enter your Twilio Account SID">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Twilio Auth Token</label>
                        <input type="password" id="password" name="password" required placeholder="Enter your Auth Token">
                    </div>
                    
                    <button type="submit" class="btn">Access System</button>
                </form>
                
                <div class="security-notice">
                    <strong>Secure Access:</strong> Your credentials are used only for this session and are not stored permanently.
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// Main application interface
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yad Moshe - SMS Campaign Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #3682de;
            --primary-hover: #2b6fc7;
            --primary-light: rgba(54, 130, 222, 0.08);
            --secondary: #868e96;
            --success: #00c853;
            --danger: #ff1744;
            --warning: #ff9800;
            --info: #2196f3;
            --bg: #f8f8f8;
            --surface: #ffffff;
            --card-bg: #ffffff;
            --text: #000000;
            --text-light: #6c757d;
            --text-muted: #adb5bd;
            --border: #e0e0e0;
            --border-light: #f0f0f0;
            --shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            --shadow-hover: 0 2px 8px rgba(0, 0, 0, 0.08);
            --shadow-card: 0 1px 4px rgba(0, 0, 0, 0.04);
            --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.08);
            --radius: 16px;
            --radius-sm: 12px;
            --radius-lg: 20px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            min-height: 100vh;
            color: var(--text);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        .header {
            background: var(--surface);
            padding: 1rem 2rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-card);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header h1 {
            color: var(--text);
            font-size: 1.5rem;
            font-weight: 600;
            letter-spacing: -0.02em;
            margin: 0;
        }
        
        .header .subtitle {
            color: var(--text-light);
            font-size: 0.875rem;
            font-weight: 400;
            margin: 0.25rem 0 0 0;
            opacity: 0.8;
        }
        
        .logout-btn {
            background: var(--surface);
            color: var(--danger);
            padding: 0.5rem 1rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: var(--shadow);
        }
        
        .logout-btn:hover {
            background: var(--danger);
            color: white;
            box-shadow: var(--shadow-hover);
            transform: translateY(-1px);
        }
        
        .container {
            width: 100%;
            margin: 1.5rem 0;
            padding: 0 1.5rem;
        }
        
        .welcome-section {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-hover) 100%);
            color: white;
            padding: 2rem;
            border-radius: var(--radius-lg);
            margin-bottom: 2rem;
            box-shadow: var(--shadow-card);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .welcome-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
            pointer-events: none;
        }
        
        .welcome-section h2 {
            margin: 0 0 0.75rem 0;
            font-size: 1.75rem;
            font-weight: 600;
            letter-spacing: -0.02em;
            position: relative;
            z-index: 1;
        }
        
        .welcome-section p {
            font-size: 1rem;
            opacity: 0.9;
            margin: 0;
            line-height: 1.6;
            position: relative;
            z-index: 1;
        }

        .debug-notice {
            background: #fef3c7;
            color: #92400e;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin: 1rem 0;
            border: 1px solid #f59e0b;
            font-size: 0.875rem;
            text-align: center;
        }
        
        
        .card {
            background: var(--surface);
            border-radius: var(--radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-card);
            border: 1px solid var(--border);
            transition: all 0.3s ease;
            position: relative;
        }

        .card:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-1px);
        }
        
        .card h2 {
            margin: 0 0 1.5rem 0;
            color: var(--text);
            font-size: 1.5rem;
            font-weight: 600;
            letter-spacing: -0.02em;
            position: relative;
        }

        .card h2::after {
            content: '';
            position: absolute;
            bottom: -0.5rem;
            left: 0;
            width: 3rem;
            height: 2px;
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
            border-radius: 1px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 0;
        }
        
        label {
            display: block;
            margin-bottom: 0.75rem;
            font-weight: 500;
            color: var(--text);
            font-size: 0.875rem;
            letter-spacing: 0.01em;
        }
        
        input[type="text"], input[type="number"], input[type="file"], textarea {
            width: 100%;
            padding: 1rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 0.95rem;
            background: var(--surface);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: inherit;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.04);
        }

        input[type="text"]:focus, input[type="number"]:focus, input[type="file"]:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-light), inset 0 1px 2px rgba(0, 0, 0, 0.04);
            background: var(--surface);
        }

        input[type="text"]:hover, input[type="number"]:hover, textarea:hover {
            border-color: var(--text-light);
        }
        
        textarea {
            resize: vertical;
            min-height: 100px;
            line-height: 1.5;
        }

        input[type="file"] {
            padding: 0.75rem;
            border-style: solid;
            background: var(--surface);
            cursor: pointer;
        }

        input[type="file"]:hover {
            border-color: var(--primary);
            background: var(--bg);
        }

        input[type="number"] {
            max-width: 120px;
        }

        small {
            display: block;
            margin-top: 0.5rem;
            color: var(--text-light);
            font-size: 0.75rem;
            line-height: 1.4;
        }
        
        
        .btn {
            padding: 0.875rem 1.5rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            margin-right: 0.5rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 1px 3px rgba(0, 122, 204, 0.3);
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn:hover {
            background: var(--primary-hover);
            box-shadow: 0 2px 8px rgba(0, 122, 204, 0.4);
            transform: translateY(-1px);
        }

        .btn:hover::before {
            left: 100%;
        }
        
        .btn:active {
            transform: translateY(0);
            box-shadow: 0 1px 3px rgba(0, 122, 204, 0.3);
        }
        
        .btn-secondary {
            background: var(--secondary);
            color: white;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        .progress-container {
            margin-top: 2rem;
            background: var(--surface);
            padding: 2rem;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-card);
        }

        .progress-bar {
            width: 100%;
            height: 24px;
            background: var(--border-light);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 1rem;
            position: relative;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--primary-hover));
            width: 0%;
            transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 12px;
            position: relative;
            overflow: hidden;
        }

        .progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            background-image: linear-gradient(
                -45deg,
                rgba(255, 255, 255, 0.3) 25%,
                transparent 25%,
                transparent 50%,
                rgba(255, 255, 255, 0.3) 50%,
                rgba(255, 255, 255, 0.3) 75%,
                transparent 75%,
                transparent
            );
            background-size: 20px 20px;
            animation: progressShimmer 2s linear infinite;
        }

        @keyframes progressShimmer {
            0% { background-position: 0 0; }
            100% { background-position: 20px 20px; }
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-bottom: 0;
        }

        .stat-card {
            background: var(--surface);
            padding: 1.5rem;
            border-radius: var(--radius);
            text-align: center;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-card);
            position: relative;
            overflow: hidden;
            transition: box-shadow 0.2s ease;
        }

        .stat-card:hover {
            box-shadow: var(--shadow-hover);
        }

        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
            line-height: 1;
            letter-spacing: -0.02em;
        }
        
        .stat-label {
            color: var(--text-light);
            font-size: 1rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
            font-weight: 500;
        }
        
        .alert-success {
            background: #f0fdf4;
            color: #166534;
            border-left-color: var(--success);
        }
        
        .alert-error {
            background: #fef2f2;
            color: #dc2626;
            border-left-color: var(--danger);
        }
        
        .alert-info {
            background: #eff6ff;
            color: #1d4ed8;
            border-left-color: var(--info);
        }
        
        
        .file-requirements {
            background: #f8fafc;
            color: var(--text-light);
            padding: 0.75rem 1rem;
            border-radius: 6px;
            margin-top: 0.5rem;
            font-size: 0.8125rem;
            border: 1px solid var(--border);
            line-height: 1.5;
        }

        .file-requirements strong {
            color: var(--text);
            font-weight: 600;
        }
        
        .char-counter {
            margin-top: 0.5rem;
            font-size: 0.8125rem;
            color: var(--text-light);
            text-align: right;
            font-weight: 500;
        }

        .char-counter #charCount {
            color: var(--text);
            font-weight: 600;
        }
        
        .button-group {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 1.5rem;
        }

        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }

        .btn-info {
            background: var(--info);
            color: white;
        }

        .btn-info:hover {
            background: #0288d1;
        }
        
        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-hover));
            color: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow-card);
        }

        .progress-header h2 {
            color: white;
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
        }
        
        .campaign-controls {
            display: flex;
            gap: 1rem;
        }

        .campaign-controls .btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
        }

        .campaign-controls .btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-1px);
        }

        .campaign-controls .btn-danger:hover {
            background: rgba(255, 0, 0, 0.8);
        }
        
        .progress-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
        }

        .status-text {
            font-weight: 600;
            color: var(--primary);
            font-size: 1rem;
        }

        .progress-text {
            color: var(--text-light);
            font-size: 0.9rem;
            background: var(--bg);
            padding: 0.5rem 1rem;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
        }
        
        .console-section {
            margin-top: 2rem;
            border-top: 2px solid var(--border);
            padding-top: 2rem;
        }
        
        .console-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .console-header h3 {
            color: var(--text);
            font-size: 1.125rem;
            font-weight: 600;
        }
        
        .console-controls {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .auto-scroll-toggle {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--text-light);
            cursor: pointer;
        }
        
        .auto-scroll-toggle input[type="checkbox"] {
            margin: 0;
        }
        
        .log-container {
            background: #1e293b;
            color: #e2e8f0;
            border-radius: 8px;
            padding: 1.5rem;
            font-family: 'Monaco', 'Menlo', 'Consolas', monospace;
            max-height: 400px;
            overflow-y: auto;
            white-space: pre-wrap;
            line-height: 1.5;
            font-size: 0.875rem;
            border: 1px solid var(--border);
            position: relative;
        }
        
        .log-entry {
            margin-bottom: 0.5rem;
            padding: 0.25rem 0;
        }
        
        .log-success {
            color: #10b981;
        }
        
        .log-error {
            color: #ef4444;
        }
        
        .log-warning {
            color: #f59e0b;
        }
        
        .log-info {
            color: #3b82f6;
        }
        
        .log-timestamp {
            color: #6b7280;
            font-weight: 500;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--surface);
            padding: 1.5rem;
            border-radius: var(--radius);
            text-align: center;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-card);
            position: relative;
            overflow: hidden;
            transition: box-shadow 0.2s ease;
        }


        .stat-card:hover {
            box-shadow: var(--shadow-hover);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
            line-height: 1;
            letter-spacing: -0.02em;
        }
        
        .stat-label {
            color: var(--text-light);
            font-size: 0.875rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--success));
            width: 0%;
            transition: width 0.4s ease;
            border-radius: 12px;
            position: relative;
            overflow: hidden;
        }
        
        .progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            background-image: linear-gradient(
                -45deg,
                rgba(255, 255, 255, 0.2) 25%,
                transparent 25%,
                transparent 50%,
                rgba(255, 255, 255, 0.2) 50%,
                rgba(255, 255, 255, 0.2) 75%,
                transparent 75%,
                transparent
            );
            background-size: 50px 50px;
            animation: move 2s linear infinite;
        }
        
        @keyframes move {
            0% { background-position: 0 0; }
            100% { background-position: 50px 50px; }
        }
        
        /* Progress Bars */
        .progress-bar {
            width: 100%;
            height: 8px;
            background: var(--border);
            border-radius: 4px;
            overflow: hidden;
            margin-top: 0.75rem;
        }

        .progress-fill {
            height: 100%;
            width: 0%;
            background: var(--primary);
            border-radius: 4px;
            transition: width 0.5s ease;
        }

        .progress-fill.success {
            background: var(--success);
        }

        .progress-fill.danger {
            background: var(--danger);
        }

        .progress-fill.warning {
            background: var(--warning);
        }

        .progress-fill.info {
            background: var(--info);
        }


        /* Responsive Design */
        @media (max-width: 1200px) {
            .container {
                padding: 0 1rem;
            }

            .form-row {
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 1.25rem;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 0.75rem;
                margin: 1rem 0;
            }

            .form-row {
                grid-template-columns: 1fr;
            gap: 1rem;
            }

            .welcome-section {
                padding: 1.25rem;
            }

            .card {
                margin-bottom: 1.5rem;
            }

            .metrics-overview {
                grid-template-columns: 1fr;
                gap: 1rem;
                margin-bottom: 2rem;
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .analytics-charts {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .analytics-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .analytics-controls {
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 0.5rem;
            }

            .welcome-section {
                padding: 1rem;
                margin-bottom: 1.5rem;
            }

            .card {
                padding: 1rem;
            }

            .metric-card {
                padding: 1.25rem;
            }

            .metric-value {
                font-size: 2.25rem;
            }

            .button-group {
                flex-direction: column;
                gap: 0.75rem;
            }

            .btn {
                width: 100%;
                margin-right: 0;
            }
        }
        
        
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }
        
        .modal-content {
            background: var(--card-bg);
            margin: 5% auto;
            padding: 2rem;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            animation: modalSlideIn 0.3s ease;
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border);
        }
        
        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text);
        }
        
        .close {
            color: var(--text-light);
            font-size: 1.5rem;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        
        .close:hover {
            color: var(--text);
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 2px solid var(--border);
        }
        
        /* Notification Styles */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 1001;
            animation: slideInRight 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .notification-success {
            background: linear-gradient(135deg, var(--success), #047857);
        }
        
        .notification-error {
            background: linear-gradient(135deg, var(--danger), #991b1b);
        }
        
        .notification-warning {
            background: linear-gradient(135deg, var(--warning), #92400e);
        }
        
        .notification-info {
            background: linear-gradient(135deg, var(--info), #0c4a6e);
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        /* Responsive Design */
        @media (max-width: 1024px) {
            .analytics-grid {
                grid-template-columns: 1fr;
            }
            
            .analytics-details {
                grid-template-columns: 1fr;
            }
            
            .templates-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .contact-actions {
                flex-direction: column;
            }
            
            .contact-filters {
                flex-direction: column;
            }
            
            .contacts-table-container {
                overflow-x: auto;
            }
            
            .modal-content {
                margin: 10% auto;
                width: 95%;
                padding: 1.5rem;
            }
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .header {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .container {
                padding: 0 1rem;
            }
            
            .welcome-section {
                padding: 1.5rem;
            }
            
            .stats {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>Yad Moshe</h1>
            <div class="subtitle">SMS Campaign Management System</div>
        </div>
        <a href="?logout=1" class="logout-btn">Sign Out</a>
    </div>
    
    <div class="container">
        
        <div class="card">
            <h2>Send Bulk SMS Campaign</h2>

            <form id="smsForm" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label for="csvFile">Contact List (CSV File)</label>
                        <input type="file" id="csvFile" name="csvFile" accept=".csv" required>
                    </div>

                    <div class="form-group">
                        <label for="fromNumber">Sender Phone Number</label>
                        <input type="text" id="fromNumber" name="fromNumber" placeholder="+1234567890" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="messageTemplate">Message Template</label>
                        <textarea id="messageTemplate" name="messageTemplate" placeholder="Hello @name, please visit: @link" required></textarea>
                        <div style="margin-top: 0.75rem;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: normal; margin-bottom: 0;">
                                <input type="checkbox" id="includeStopText" name="includeStopText" checked style="width: auto; margin: 0;">
                                <span>Automatically add "Reply STOP to stop" text</span>
                            </label>
                        </div>
                        <div class="char-counter">
                            <span id="charCount">0</span> characters<span id="stopTextNote"> (including auto-added STOP text)</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="baseUrl">Base URL for @link Variable</label>
                        <input type="text" id="baseUrl" name="baseUrl" placeholder="https://example.com/?id=" value="https://example.com/?id=" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="imageFile">Image/Photo (Optional - for MMS)</label>
                        <input type="file" id="imageFile" name="imageFile" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                        <small>Upload an image to send as MMS along with the text message. Each recipient will receive a separate message with the image.</small>
                        <div id="imagePreview" style="margin-top: 1rem; display: none;">
                            <img id="imagePreviewImg" src="" alt="Preview" style="max-width: 300px; max-height: 200px; border-radius: 8px; border: 1px solid var(--border);">
                            <button type="button" id="removeImageBtn" class="btn btn-danger btn-sm" style="margin-top: 0.5rem;">Remove Image</button>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="batchSize">Concurrent Batch Size</label>
                        <input type="number" id="batchSize" name="batchSize" placeholder="100" value="100" min="10" max="500" required>
                        <small>Number of contacts to process in each concurrent batch (10-500)</small>
                    </div>

                    <div class="form-group">
                        <label for="maxConcurrent">Max Concurrent Requests</label>
                        <input type="number" id="maxConcurrent" name="maxConcurrent" placeholder="25" value="25" min="1" max="50" required>
                        <small>Maximum SMS requests to send simultaneously (1-50)</small>
                    </div>
                </div>


                <div class="button-group">
                    <button type="submit" class="btn" id="sendBtn">Send SMS Campaign</button>
                    <button type="button" class="btn btn-secondary" id="previewBtn">Preview Messages</button>
                    <?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
                    <button type="button" class="btn btn-info" id="debugBtn">Debug Mode</button>
                    <button type="button" class="btn btn-warning" id="testConcurrentBtn">🚀 Test Concurrent</button>
                    <?php endif; ?>
                    <button type="button" class="btn btn-danger" id="stopBtn" style="display: none;">Stop Campaign</button>
                </div>
            </form>
        </div>
        
        <div class="card" id="progressCard" style="display: none;">
            <div class="progress-header">
                <h2>Campaign Progress</h2>
                <div class="campaign-controls">
                    <button type="button" class="btn btn-danger btn-sm" id="stopCampaignBtn">Stop Campaign</button>
                    <button type="button" class="btn btn-secondary btn-sm" id="pauseCampaignBtn" style="display: none;">Pause Campaign</button>
                    <button type="button" class="btn btn-success btn-sm" id="resumeCampaignBtn" style="display: none;">Resume Campaign</button>
                </div>
            </div>
            
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-number" id="totalCount">0</div>
                    <div class="stat-label">Total Recipients</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="sentCount">0</div>
                    <div class="stat-label">Messages Sent</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="successCount">0</div>
                    <div class="stat-label">Delivered</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="failedCount">0</div>
                    <div class="stat-label">Failed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="successRate">0%</div>
                    <div class="stat-label">Success Rate</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="elapsedTime">00:00:00</div>
                    <div class="stat-label">Elapsed Time</div>
                </div>
            </div>
            
            <div class="progress-container">
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
                <div class="progress-info">
                    <div id="currentStatus" class="status-text">
                        Preparing campaign...
                    </div>
                    <div id="progressText" class="progress-text">
                        0 of 0 messages sent
                    </div>
                </div>
            </div>
            
            <div class="console-section">
                <div class="console-header">
                    <h3>Real-time Console</h3>
                    <div class="console-controls">
                        <button type="button" class="btn btn-sm btn-secondary" id="clearConsoleBtn">Clear</button>
                        <button type="button" class="btn btn-sm btn-secondary" id="exportLogsBtn">Export Logs</button>
                        <label class="auto-scroll-toggle">
                            <input type="checkbox" id="autoScrollToggle" checked>
                            Auto-scroll
                        </label>
                    </div>
                </div>
                <div class="log-container" id="logContainer"></div>
            </div>
        </div>
        
        <div class="card" id="previewCard" style="display: none;">
            <h2>Message Preview</h2>
            <div id="previewContent"></div>
        </div>
        
            
            
            </div>

        
            </div>
        </div>
        
        
    </div>

    <script>
        // Global variables
        let campaignActive = false;
        let campaignStartTime = null;
        let progressInterval = null;
        let currentProcessId = null;
        let autoScrollEnabled = true;
        let logs = [];
        let analyticsData = {};
        let charts = {};

        // Initialize the application
        document.addEventListener('DOMContentLoaded', function() {
            initializeEventListeners();
            updateCharCounter();
        });

        function initializeEventListeners() {
            // Form submission
            document.getElementById('smsForm').addEventListener('submit', handleFormSubmit);
            
            // Preview button
            document.getElementById('previewBtn').addEventListener('click', handlePreview);

            // Debug buttons (only if debug mode is enabled)
            <?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
            if (document.getElementById('debugBtn')) {
                document.getElementById('debugBtn').addEventListener('click', handleDebug);
            }
            if (document.getElementById('testConcurrentBtn')) {
                document.getElementById('testConcurrentBtn').addEventListener('click', handleTestConcurrent);
            }
            <?php endif; ?>
            
            // Character counter
            document.getElementById('messageTemplate').addEventListener('input', updateCharCounter);
            document.getElementById('includeStopText').addEventListener('change', updateCharCounter);
            
            // Image preview
            document.getElementById('imageFile').addEventListener('change', handleImagePreview);
            document.getElementById('removeImageBtn').addEventListener('click', removeImage);
            
            // Campaign controls
            document.getElementById('stopCampaignBtn').addEventListener('click', stopCampaign);
            document.getElementById('pauseCampaignBtn').addEventListener('click', pauseCampaign);
            document.getElementById('resumeCampaignBtn').addEventListener('click', resumeCampaign);
            
            // Console controls
            document.getElementById('clearConsoleBtn').addEventListener('click', clearConsole);
            document.getElementById('exportLogsBtn').addEventListener('click', exportLogs);
            document.getElementById('autoScrollToggle').addEventListener('change', toggleAutoScroll);
            
            
            
            
            // Initialize data
            initializeNotifications();
        }

        function updateCharCounter() {
            const template = document.getElementById('messageTemplate').value;
            const includeStopText = document.getElementById('includeStopText').checked;
            const stopText = '\n\nReply STOP to stop';
            const totalLength = includeStopText ? template.length + stopText.length : template.length;
            document.getElementById('charCount').textContent = totalLength;
            
            // Update the note about STOP text
            const stopTextNote = document.getElementById('stopTextNote');
            if (includeStopText) {
                stopTextNote.textContent = ' (including auto-added STOP text)';
            } else {
                stopTextNote.textContent = '';
            }
            
            // Color code based on SMS length limits
            const charCountElement = document.getElementById('charCount');
            if (totalLength <= 160) {
                charCountElement.style.color = '#059669';
            } else if (totalLength <= 320) {
                charCountElement.style.color = '#d97706';
            } else {
                charCountElement.style.color = '#dc2626';
            }
        }

        function handleImagePreview(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file type
                if (!file.type.match('image.*')) {
                    showAlert('Please select a valid image file (JPEG, PNG, GIF, or WebP).', 'error');
                    e.target.value = '';
                    return;
                }
                
                // Validate file size (max 5MB for MMS)
                if (file.size > 5 * 1024 * 1024) {
                    showAlert('Image file is too large. Maximum size is 5MB for MMS.', 'error');
                    e.target.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('imagePreviewImg').src = e.target.result;
                    document.getElementById('imagePreview').style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        }

        function removeImage() {
            document.getElementById('imageFile').value = '';
            document.getElementById('imagePreview').style.display = 'none';
            document.getElementById('imagePreviewImg').src = '';
        }

        function handlePreview() {
            const csvFile = document.getElementById('csvFile').files[0];
            const messageTemplate = document.getElementById('messageTemplate').value;
            const baseUrl = document.getElementById('baseUrl').value;
            const imageFile = document.getElementById('imageFile').files[0];
            
            if (!csvFile || !messageTemplate) {
                showAlert('Please select a CSV file and enter a message template first.', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'preview');
            formData.append('csvFile', csvFile);
            formData.append('messageTemplate', messageTemplate);
            formData.append('baseUrl', baseUrl);
            if (document.getElementById('includeStopText').checked) {
                formData.append('includeStopText', 'on');
            }
            if (imageFile) {
                formData.append('imageFile', imageFile);
            }
            
            // Add debug parameter if needed (you can enable this for troubleshooting)
            const url = 'process.php' + (window.location.search.includes('debug=1') ? '?debug=1' : '');
            
            showLoading('Generating preview...');
            
            fetch(url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    document.getElementById('previewContent').innerHTML = data.preview;
                    document.getElementById('previewCard').style.display = 'block';
                    document.getElementById('previewCard').scrollIntoView({ behavior: 'smooth' });
                } else {
                    showAlert('Error: ' + data.error, 'error');
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                showAlert('An error occurred while generating preview.', 'error');
            });
        }

        function handleTestConcurrent() {
            const messageTemplate = document.getElementById('messageTemplate').value;
            const baseUrl = document.getElementById('baseUrl').value;
            const fromNumber = document.getElementById('fromNumber').value;
            const batchSize = document.getElementById('batchSize').value || 100;
            const maxConcurrent = document.getElementById('maxConcurrent').value || 25;

            if (!messageTemplate || !fromNumber) {
                showAlert('Please enter a message template and sender phone number for testing.', 'error');
                return;
            }

            // Ask user for test size
            const testContacts = prompt('How many test contacts would you like to use? (10-1000)', '100');
            if (!testContacts || testContacts < 10 || testContacts > 1000) {
                showAlert('Please enter a valid number of test contacts (10-1000).', 'warning');
                return;
            }

            // Show progress card for test
            showProgressCard();
            campaignActive = true;
            campaignStartTime = new Date();

            // Update UI for testing
            document.getElementById('sendBtn').disabled = true;
            document.getElementById('previewBtn').disabled = true;

            <?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
            if (document.getElementById('debugBtn')) {
                document.getElementById('debugBtn').disabled = true;
                document.getElementById('debugBtn').textContent = 'Test Running...';
            }
            if (document.getElementById('testConcurrentBtn')) {
                document.getElementById('testConcurrentBtn').disabled = true;
                document.getElementById('testConcurrentBtn').textContent = 'Test Running...';
            }
            <?php endif; ?>

            document.getElementById('sendBtn').textContent = 'Test Running...';
            document.getElementById('stopBtn').style.display = 'inline-block';

            // Prepare test data
            const formData = new FormData();
            formData.append('action', 'test_concurrent');
            formData.append('testContacts', testContacts);
            formData.append('messageTemplate', messageTemplate);
            formData.append('baseUrl', baseUrl);
            formData.append('fromNumber', fromNumber);
            formData.append('batchSize', batchSize);
            formData.append('maxConcurrent', maxConcurrent);

            // Start test with streaming response (like real SMS sending)
            fetch('process.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                // Handle streaming response
                const reader = response.body.getReader();
                const decoder = new TextDecoder();

                function readStream() {
                    return reader.read().then(({ done, value }) => {
                        if (done) {
                            // Test completed
                            campaignActive = false;
                            resetCampaignUI();
                            showNotification('Concurrent SMS test completed successfully!', 'success');
                            return;
                        }

                        // Decode the chunk
                        const chunk = decoder.decode(value, { stream: true });
                        const lines = chunk.split('\n').filter(line => line.trim());

                        // Process each line (like real SMS sending)
                        lines.forEach(line => {
                            if (line.includes('SUCCESS:') || line.includes('FAILED:')) {
                                addLogEntryToConsole(line);
                                // Extract progress info from the line
                                updateProgressFromTestLog(line);
                            } else if (line.includes('Progress:')) {
                                // Extract progress info from the line
                                updateProgressFromTestLog(line);
                            } else if (line.includes('Test batch') && line.includes('completed')) {
                                addLogEntryToConsole(line);
                            } else if (line.includes('Batch Rate:')) {
                                addLogEntryToConsole(line);
                            } else if (line.includes('CONCURRENT SMS TEST COMPLETED')) {
                                addLogEntryToConsole(line);
                                campaignActive = false;
                            } else if (line.includes('========================================')) {
                                // Separator line
                                addLogEntryToConsole(line);
                            } else if (line.includes('Total Contacts:') || line.includes('Messages Processed:') ||
                                       line.includes('Successful:') || line.includes('Failed:') ||
                                       line.includes('Success Rate:') || line.includes('Processing Time:') ||
                                       line.includes('Messages/Second:') || line.includes('Estimated Real SMS Rate:')) {
                                addLogEntryToConsole(line);
                            }
                        });

                        // Continue reading
                        return readStream();
                    });
                }

                // Start reading the stream
                readStream().catch(error => {
                    console.error('Error reading test stream:', error);
                    showAlert('An error occurred during testing.', 'error');
                    resetCampaignUI();
                });

                // Add initial log entry
                addLogEntry('Concurrent SMS test started successfully', 'info');
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred while starting the test.', 'error');
                resetCampaignUI();
            });
        }

        function showConcurrentTestResults(data) {
            const performance = data.performance;
            const summary = data.summary;

            // Create results modal
            const modalContent = `
                <h3>🚀 Concurrent SMS Test Results</h3>
                <div style="background: #f8fafc; padding: 1rem; border-radius: 8px; margin: 1rem 0;">
                    <h4 style="color: #1e40af; margin-bottom: 1rem;">📊 Performance Summary</h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <div style="background: white; padding: 1rem; border-radius: 6px; border: 1px solid #e2e8f0;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: #059669;">${summary.rate}</div>
                            <div style="color: #6b7280; font-size: 0.9rem;">Messages/Second</div>
                        </div>
                        <div style="background: white; padding: 1rem; border-radius: 6px; border: 1px solid #e2e8f0;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: #dc2626;">${summary.processing_time}</div>
                            <div style="color: #6b7280; font-size: 0.9rem;">Total Time</div>
                        </div>
                        <div style="background: white; padding: 1rem; border-radius: 6px; border: 1px solid #e2e8f0;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: #7c3aed;">${summary.contacts_tested}</div>
                            <div style="color: #6b7280; font-size: 0.9rem;">Test Contacts</div>
                        </div>
                        <div style="background: white; padding: 1rem; border-radius: 6px; border: 1px solid #e2e8f0;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: #ea580c;">${summary.batch_count}</div>
                            <div style="color: #6b7280; font-size: 0.9rem;">Batches</div>
                        </div>
                    </div>
                </div>

                <div style="background: #fefce8; border: 1px solid #fde047; padding: 1rem; border-radius: 8px; margin: 1rem 0;">
                    <h4 style="color: #92400e; margin-bottom: 0.5rem;">⚡ Expected Real Performance</h4>
                    <p style="margin: 0; color: #92400e;">
                        <strong>${performance.estimatedRealSMSRate} msg/sec</strong> estimated for actual SMS sending (accounting for Twilio API overhead)
                    </p>
                </div>

                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 1rem; border-radius: 8px; margin: 1rem 0;">
                    <h4 style="color: #166534; margin-bottom: 0.5rem;">📈 Configuration Used</h4>
                    <ul style="margin: 0; padding-left: 1.5rem; color: #166534;">
                        <li><strong>Batch Size:</strong> ${performance.batchSize} contacts</li>
                        <li><strong>Max Concurrent:</strong> ${performance.maxConcurrent} requests</li>
                        <li><strong>Avg Batch Time:</strong> ${performance.avgBatchTime} seconds</li>
                        <li><strong>Memory Usage:</strong> ${formatBytes(performance.memoryUsage)}</li>
                    </ul>
                </div>

                <div style="background: #eff6ff; border: 1px solid #bfdbfe; padding: 1rem; border-radius: 8px; margin: 1rem 0;">
                    <h4 style="color: #1e40af; margin-bottom: 0.5rem;">💡 Performance Analysis</h4>
                    <p style="margin: 0 0 0.5rem 0; color: #1e40af;">
                        Your concurrent SMS system processed <strong>${summary.contacts_tested}</strong> contacts in <strong>${summary.processing_time}</strong>,
                        achieving <strong>${summary.rate}</strong>!
                    </p>
                    <p style="margin: 0; color: #1e40af;">
                        This is approximately <strong>${Math.round(performance.messagesPerSecond / 2 * 100)}x faster</strong> than typical sequential sending.
                    </p>
                </div>
            `;

            // Create and show modal
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content" style="max-width: 800px;">
                    <div class="modal-header">
                        <h2 class="modal-title">Concurrent SMS Test Results</h2>
                        <span class="close" style="cursor: pointer; font-size: 1.5rem;">&times;</span>
                    </div>
                    <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                        ${modalContent}
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" onclick="this.closest('.modal').remove()">Close</button>
                        <button class="btn" onclick="location.reload()">Run Another Test</button>
                    </div>
                </div>
            `;

            // Add event listeners
            modal.querySelector('.close').onclick = () => modal.remove();
            modal.onclick = (e) => { if (e.target === modal) modal.remove(); };

            document.body.appendChild(modal);
        }

        function updateProgressFromTestLog(logLine) {
            // Extract progress information from test log lines
            if (logLine.includes('Progress:')) {
                const progressMatch = logLine.match(/Progress:\s*(\d+)\/(\d+)\s*\(Success:\s*(\d+),\s*Failed:\s*(\d+)\)/);
                if (progressMatch) {
                    const sent = parseInt(progressMatch[1]);
                    const total = parseInt(progressMatch[2]);
                    const success = parseInt(progressMatch[3]);
                    const failed = parseInt(progressMatch[4]);

                    // Update UI elements (like real SMS sending)
                    document.getElementById('totalCount').textContent = total;
                    document.getElementById('sentCount').textContent = sent;
                    document.getElementById('successCount').textContent = success;
                    document.getElementById('failedCount').textContent = failed;

                    // Calculate success rate
                    const successRate = sent > 0 ? Math.round((success / sent) * 100) : 0;
                    document.getElementById('successRate').textContent = successRate + '%';

                    // Update progress bar
                    const progress = total > 0 ? (sent / total) * 100 : 0;
                    document.getElementById('progressFill').style.width = progress + '%';

                    // Update status
                    document.getElementById('currentStatus').textContent = 'Running Concurrent Test...';
                    document.getElementById('progressText').textContent = `${sent} of ${total} test messages processed`;

                    // Update elapsed time
                    if (campaignStartTime) {
                        const elapsed = Math.floor((new Date() - campaignStartTime) / 1000);
                        document.getElementById('elapsedTime').textContent = formatTime(elapsed);
                    }
                }
            }
        }

        function formatBytes(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function handleDebug() {
            const csvFile = document.getElementById('csvFile').files[0];
            const messageTemplate = document.getElementById('messageTemplate').value;
            const baseUrl = document.getElementById('baseUrl').value;
            const fromNumber = document.getElementById('fromNumber').value;

            if (!messageTemplate || !fromNumber) {
                showAlert('Please enter a message template and sender phone number for debug mode.', 'error');
                return;
            }

            // Show progress card for simulation
            showProgressCard();
            campaignActive = true;
            campaignStartTime = new Date();

            // Update UI for debug mode
            document.getElementById('sendBtn').disabled = true;
            document.getElementById('debugBtn').disabled = true;
            document.getElementById('previewBtn').disabled = true;
            document.getElementById('sendBtn').textContent = 'Debug Running...';
            document.getElementById('debugBtn').textContent = 'Debug Running...';
            document.getElementById('stopBtn').style.display = 'inline-block';

            addLogEntry('DEBUG MODE: Starting simulation...', 'info');

            // Variables to track simulation progress
            let totalContacts = 0;
            let sentCount = 0;
            let successCount = 0;
            const batchSize = 10; // Smaller batch for debug mode

            // Global variable to control simulation
            let debugSimulationActive = true;

            // Function to stop debug simulation
            function stopDebugSimulation() {
                debugSimulationActive = false;

                // Calculate partial statistics
                const failedCount = sentCount - successCount;
                const successRate = sentCount > 0 ? Math.round((successCount / sentCount) * 100) : 0;
                const elapsed = campaignStartTime ? Math.floor((new Date() - campaignStartTime) / 1000) : 0;

                addLogEntry('DEBUG SIMULATION STOPPED BY USER', 'warning');
                addLogEntry('Partial Results:', 'info');
                addLogEntry(`   Messages Processed: ${sentCount}/${totalContacts}`, 'info');
                addLogEntry(`   Successful Deliveries: ${successCount}`, 'info');
                addLogEntry(`   Failed Deliveries: ${failedCount}`, 'info');
                addLogEntry(`   Current Success Rate: ${successRate}%`, successRate >= 90 ? 'success' : successRate >= 70 ? 'warning' : 'error');
                addLogEntry(`   Time Elapsed: ${Math.floor(elapsed / 60)}:${(elapsed % 60).toString().padStart(2, '0')}`, 'info');

                // Update final display
                document.getElementById('progressText').textContent = `${sentCount} of ${totalContacts} messages (stopped)`;
                document.getElementById('currentStatus').textContent = 'Debug Simulation Stopped';

                campaignActive = false;
                resetCampaignUI();
                document.getElementById('sendBtn').textContent = 'Initiate Campaign';
                document.getElementById('debugBtn').textContent = 'Debug Mode';
                document.getElementById('debugBtn').disabled = false;
                document.getElementById('previewBtn').disabled = false;

                showNotification('Debug simulation stopped by user', 'warning');
            }

            // Override stop button to handle debug stopping
            const originalStopBtn = document.getElementById('stopBtn');
            const originalStopHandler = () => stopDebugSimulation();
            originalStopBtn.onclick = originalStopHandler;

            // Generate fake data or use CSV
            if (csvFile) {
                addLogEntry('Processing uploaded CSV file...', 'info');

                const reader = new FileReader();
                reader.onload = function(e) {
                    const csvText = e.target.result;
                    const lines = csvText.split('\n').filter(line => line.trim());
                    totalContacts = lines.length - 1; // Subtract header row
                    addLogEntry(`Found ${totalContacts} contacts in CSV file`, 'success');
                    startSimulation();
                };
                reader.readAsText(csvFile);
            } else {
                // Generate fake data
                totalContacts = Math.floor(Math.random() * 50) + 20; // 20-70 fake contacts
                addLogEntry(`No CSV file provided - generating ${totalContacts} fake contacts for testing`, 'info');
                addLogEntry('Fake contacts: John Doe, Jane Smith, etc.', 'info');
                startSimulation();
            }

            function startSimulation() {
                function simulateBatch() {
                    if (!debugSimulationActive) return;

                    const batch = Math.min(batchSize, totalContacts - sentCount);
                    if (batch <= 0) {
                        // Simulation complete
                        if (debugSimulationActive) {
                            debugSimulationActive = false; // Stop the simulation

                            // Calculate final statistics
                            const failedCount = sentCount - successCount;
                            const successRate = sentCount > 0 ? Math.round((successCount / sentCount) * 100) : 0;
                            const elapsed = campaignStartTime ? Math.floor((new Date() - campaignStartTime) / 1000) : 0;

                            // Show completion messages
                            addLogEntry('DEBUG SIMULATION COMPLETE', 'success');
                            addLogEntry('Final Statistics:', 'info');
                            addLogEntry(`   Total Recipients: ${totalContacts}`, 'info');
                            addLogEntry(`   Messages Sent: ${sentCount}`, 'info');
                            addLogEntry(`   Successful Deliveries: ${successCount}`, 'info');
                            addLogEntry(`   Failed Deliveries: ${failedCount}`, 'info');
                            addLogEntry(`   Success Rate: ${successRate}%`, successRate >= 90 ? 'success' : successRate >= 70 ? 'warning' : 'error');
                            addLogEntry(`   Simulation Duration: ${Math.floor(elapsed / 60)}:${(elapsed % 60).toString().padStart(2, '0')}`, 'info');

                            // Update final progress display
                            document.getElementById('totalCount').textContent = totalContacts;
                            document.getElementById('sentCount').textContent = sentCount;
                            document.getElementById('successCount').textContent = successCount;
                            document.getElementById('failedCount').textContent = failedCount;
                            document.getElementById('successRate').textContent = successRate + '%';
                            document.getElementById('progressFill').style.width = '100%';
                            document.getElementById('progressText').textContent = `${sentCount} of ${totalContacts} messages completed`;
                            document.getElementById('currentStatus').textContent = 'Debug Simulation Complete';

                            // Show completion alert and analytics
                            setTimeout(() => {
                                showCompletionAlert();
                                showNotification('Debug simulation completed successfully!', 'success');
                            }, 1000);
                        }

                        campaignActive = false;
                        resetCampaignUI();
                        document.getElementById('sendBtn').textContent = 'Initiate Campaign';
                        document.getElementById('debugBtn').textContent = 'Debug Mode';
                        document.getElementById('debugBtn').disabled = false;
                        document.getElementById('previewBtn').disabled = false;
                        return;
                    }

                    addLogEntry(`Processing group of ${batch} messages...`, 'info');

                    // Simulate processing each message in batch
                    for (let i = 0; i < batch; i++) {
                        if (!debugSimulationActive) break;

                        setTimeout(() => {
                            if (!debugSimulationActive) return;

                            const contactIndex = sentCount + i;
                            const isSuccess = Math.random() > 0.1; // 90% success rate for simulation

                            if (isSuccess) {
                                const fakeNames = ['John Doe', 'Jane Smith', 'Bob Johnson', 'Alice Brown', 'Charlie Wilson', 'Diana Davis', 'Edward Miller', 'Fiona Garcia'];
                                const fakeName = fakeNames[Math.floor(Math.random() * fakeNames.length)];
                                addLogEntry(`SUCCESS: Message ${contactIndex + 1} sent to ${fakeName} (+1234567890)`, 'success');
                                successCount++;
                            } else {
                                addLogEntry(`FAILED: Message ${contactIndex + 1} failed to send - simulated network error`, 'error');
                            }
                        }, i * 200); // Stagger the messages
                    }

                    sentCount += batch;

                    // Update progress
                    document.getElementById('totalCount').textContent = totalContacts;
                    document.getElementById('sentCount').textContent = sentCount;
                    document.getElementById('successCount').textContent = successCount;
                    document.getElementById('failedCount').textContent = sentCount - successCount;

                    const successRate = sentCount > 0 ? Math.round((successCount / sentCount) * 100) : 0;
                    document.getElementById('successRate').textContent = successRate + '%';

                    const progress = (sentCount / totalContacts) * 100;
                    document.getElementById('progressFill').style.width = progress + '%';
                    document.getElementById('progressText').textContent = `${sentCount} of ${totalContacts} messages simulated`;
                    document.getElementById('currentStatus').textContent = 'Debug Simulation Running...';

                    // Process next batch after delay
                    if (debugSimulationActive) {
                        setTimeout(simulateBatch, 2000);
                    }
                }

                // Start simulation after brief delay
                setTimeout(simulateBatch, 1000);
            }
        }

        function handleFormSubmit(e) {
            e.preventDefault();
            
            if (campaignActive) {
                showAlert('A campaign is already running. Please stop it first.', 'warning');
                return;
            }
            
            const formData = new FormData(document.getElementById('smsForm'));
            formData.append('action', 'send');
            
            // Image file is already included in formData if selected
            
            // Show progress card
            showProgressCard();
            campaignActive = true;
            campaignStartTime = new Date();
            
            // Update UI
            document.getElementById('sendBtn').disabled = true;
            document.getElementById('sendBtn').textContent = 'Sending...';
            document.getElementById('stopCampaignBtn').style.display = 'inline-block';

            <?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
            if (document.getElementById('debugBtn')) {
                document.getElementById('debugBtn').disabled = true;
            }
            if (document.getElementById('testConcurrentBtn')) {
                document.getElementById('testConcurrentBtn').disabled = true;
            }
            <?php endif; ?>

            document.getElementById('previewBtn').disabled = true;
            
            // Start sending process with streaming response
            fetch('process.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                
                // Handle streaming response
                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                
                function readStream() {
                    return reader.read().then(({ done, value }) => {
                        if (done) {
                            return;
                        }
                        
                        // Decode the chunk
                        const chunk = decoder.decode(value, { stream: true });
                        const lines = chunk.split('\n').filter(line => line.trim());
                        
                        // Process each line
                        lines.forEach(line => {
                            if (line.includes('SUCCESS:') || line.includes('FAILED:')) {
                                addLogEntryToConsole(line);
                                // Extract progress info from the line
                                updateProgressFromLog(line);
                            } else if (line.includes('Progress:')) {
                                addLogEntryToConsole(line);
                                // Extract progress info from the line
                                updateProgressFromLog(line);
                            } else if (line.includes('Group complete')) {
                                addLogEntryToConsole(line);
                            } else if (line.includes('CAMPAIGN COMPLETED')) {
                                addLogEntryToConsole(line);
                                campaignActive = false;
                                resetCampaignUI();
                                showCompletionAlert();
                            } else if (line.includes('---')) {
                                // Separator line
                                addLogEntryToConsole(line);
                            }
                        });
                        
                        // Continue reading
                        return readStream();
                    });
                }
                
                // Start reading the stream
                readStream().catch(error => {
                    console.error('Error reading stream:', error);
                    showAlert('An error occurred while reading the response.', 'error');
                    resetCampaignUI();
                });
                
                // Add initial log entry
                addLogEntry('Campaign started successfully', 'success');
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred while starting the campaign.', 'error');
                resetCampaignUI();
            });
        }

        function startProgressPolling() {
            if (progressInterval) {
                clearInterval(progressInterval);
            }
            
            progressInterval = setInterval(() => {
                fetch('progress.php')
                    .then(response => response.json())
                    .then(data => {
                        updateProgress(data);
                        
                        if (data.status === 'completed' || data.status === 'error' || data.status === 'stopped') {
                            clearInterval(progressInterval);
                            campaignActive = false;
                            resetCampaignUI();
                            
                            if (data.status === 'completed') {
                                showCompletionAlert();
                            } else if (data.status === 'stopped') {
                                addLogEntry('Campaign stopped by user', 'warning');
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error polling progress:', error);
                    });
            }, 1000);
        }

        function updateProgress(data) {
            // Update counters
            document.getElementById('totalCount').textContent = data.total || 0;
            document.getElementById('sentCount').textContent = data.sent || 0;
            document.getElementById('successCount').textContent = data.success || 0;
            document.getElementById('failedCount').textContent = data.failed || 0;
            
            // Calculate success rate
            const successRate = data.sent > 0 ? Math.round((data.success / data.sent) * 100) : 0;
            document.getElementById('successRate').textContent = successRate + '%';
            
            // Update progress bar
            const progress = data.total > 0 ? (data.sent / data.total) * 100 : 0;
            document.getElementById('progressFill').style.width = progress + '%';
            
            // Update status
            document.getElementById('currentStatus').textContent = getStatusMessage(data.status || 'processing');
            document.getElementById('progressText').textContent = `${data.sent || 0} of ${data.total || 0} messages sent`;
            
            // Update elapsed time
            if (campaignStartTime) {
                const elapsed = Math.floor((new Date() - campaignStartTime) / 1000);
                document.getElementById('elapsedTime').textContent = formatTime(elapsed);
            }
            
            // Update logs
            if (data.logs) {
                updateLogs(data.logs);
            }
            
            // Add pulse animation to active counters
            if (data.status === 'running') {
                document.getElementById('sentCount').classList.add('pulse');
                document.getElementById('successCount').classList.add('pulse');
            } else {
                document.getElementById('sentCount').classList.remove('pulse');
                document.getElementById('successCount').classList.remove('pulse');
            }
        }

        function updateProgressFromLog(logLine) {
            // Extract progress information from log lines
            if (logLine.includes('Progress:')) {
                const progressMatch = logLine.match(/Progress:\s*(\d+)\/(\d+)\s*\(Success:\s*(\d+),\s*Failed:\s*(\d+)\)/);
                if (progressMatch) {
                    const sent = parseInt(progressMatch[1]);
                    const total = parseInt(progressMatch[2]);
                    const success = parseInt(progressMatch[3]);
                    const failed = parseInt(progressMatch[4]);
                    
                    // Update UI elements
                    document.getElementById('totalCount').textContent = total;
                    document.getElementById('sentCount').textContent = sent;
                    document.getElementById('successCount').textContent = success;
                    document.getElementById('failedCount').textContent = failed;
                    
                    // Calculate success rate
                    const successRate = sent > 0 ? Math.round((success / sent) * 100) : 0;
                    document.getElementById('successRate').textContent = successRate + '%';
                    
                    // Update progress bar
                    const progress = total > 0 ? (sent / total) * 100 : 0;
                    document.getElementById('progressFill').style.width = progress + '%';
                    
                    // Update status
                    document.getElementById('currentStatus').textContent = 'Sending Messages...';
                    document.getElementById('progressText').textContent = `${sent} of ${total} messages sent`;
                    
                    // Update elapsed time
                    if (campaignStartTime) {
                        const elapsed = Math.floor((new Date() - campaignStartTime) / 1000);
                        document.getElementById('elapsedTime').textContent = formatTime(elapsed);
                    }
                }
            }
        }

        function updateLogs(logText) {
            const logContainer = document.getElementById('logContainer');
            const newLogs = logText.split('\n').filter(line => line.trim());
            
            // Only add new log entries
            newLogs.forEach(logLine => {
                if (!logs.includes(logLine)) {
                    logs.push(logLine);
                    addLogEntryToConsole(logLine);
                }
            });

            // Auto-scroll if enabled
            autoScrollToBottom();
        }

        function addLogEntryToConsole(logLine) {
            const logContainer = document.getElementById('logContainer');
            const logEntry = document.createElement('div');
            logEntry.className = 'log-entry fade-in';

            // Color code log entries
            if (logLine.includes('SUCCESS')) {
                logEntry.classList.add('log-success');
            } else if (logLine.includes('FAILED') || logLine.includes('ERROR')) {
                logEntry.classList.add('log-error');
            } else if (logLine.includes('WARNING') || logLine.includes('Pausing')) {
                logEntry.classList.add('log-warning');
            } else {
                logEntry.classList.add('log-info');
            }

            // Extract timestamp
            const timestampMatch = logLine.match(/^\[(.*?)\]/);
            if (timestampMatch) {
                const timestamp = timestampMatch[1];
                const content = logLine.replace(/^\[.*?\]\s*/, '');
                logEntry.innerHTML = `<span class="log-timestamp">[${timestamp}]</span> ${content}`;
            } else {
                logEntry.textContent = logLine;
            }

            logContainer.appendChild(logEntry);

            // Auto-scroll if enabled
            autoScrollToBottom();
        }

        function addLogEntry(message, type = 'info') {
            const timestamp = new Date().toLocaleString();
            const logLine = `[${timestamp}] ${message}`;
            logs.push(logLine);
            addLogEntryToConsole(logLine);
        }

        function stopCampaign() {
            if (!campaignActive) return;
            
            if (confirm('Are you sure you want to stop the current campaign?')) {
                fetch('process.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=stop&processId=' + currentProcessId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        addLogEntry('Stop command sent to campaign', 'warning');
                    } else {
                        showAlert('Error stopping campaign: ' + data.error, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error stopping campaign:', error);
                    showAlert('Error stopping campaign', 'error');
                });
            }
        }

        function pauseCampaign() {
            // Implementation for pause functionality
            addLogEntry('Pause functionality not yet implemented', 'info');
        }

        function resumeCampaign() {
            // Implementation for resume functionality
            addLogEntry('Resume functionality not yet implemented', 'info');
        }

        function clearConsole() {
            document.getElementById('logContainer').innerHTML = '';
            logs = [];
        }

        function exportLogs() {
            const logText = logs.join('\n');
            const blob = new Blob([logText], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `sms_campaign_logs_${new Date().toISOString().slice(0, 19).replace(/:/g, '-')}.txt`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }

        function toggleAutoScroll() {
            autoScrollEnabled = document.getElementById('autoScrollToggle').checked;
        }

        function autoScrollToBottom() {
            const logContainer = document.getElementById('logContainer');
            if (logContainer && autoScrollEnabled) {
                // Try multiple methods for reliable autoscrolling
                const scrollToBottom = () => {
                    logContainer.scrollTop = logContainer.scrollHeight;
                };

                // Immediate attempt
                scrollToBottom();

                // Backup with setTimeout
                setTimeout(scrollToBottom, 10);

                // Additional backup with requestAnimationFrame
                if (typeof requestAnimationFrame !== 'undefined') {
                    requestAnimationFrame(() => {
                        setTimeout(scrollToBottom, 50);
                    });
                }
            }
        }

        function showProgressCard() {
            document.getElementById('progressCard').style.display = 'block';
            document.getElementById('progressCard').scrollIntoView({ behavior: 'smooth' });
        }

        function resetCampaignUI() {
            campaignActive = false;
            document.getElementById('sendBtn').disabled = false;
            document.getElementById('sendBtn').textContent = 'Send SMS Campaign';

            <?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
            if (document.getElementById('debugBtn')) {
                document.getElementById('debugBtn').disabled = false;
                document.getElementById('debugBtn').textContent = 'Debug Mode';
            }
            if (document.getElementById('testConcurrentBtn')) {
                document.getElementById('testConcurrentBtn').disabled = false;
                document.getElementById('testConcurrentBtn').textContent = '🚀 Test Concurrent';
            }
            <?php endif; ?>

            document.getElementById('previewBtn').disabled = false;
            document.getElementById('stopCampaignBtn').style.display = 'none';
            document.getElementById('pauseCampaignBtn').style.display = 'none';
            document.getElementById('resumeCampaignBtn').style.display = 'none';
            document.getElementById('stopBtn').style.display = 'none';

            if (progressInterval) {
                clearInterval(progressInterval);
                progressInterval = null;
            }
        }

        function getStatusMessage(status) {
            const messages = {
                'idle': 'Ready to start campaign',
                'starting': 'Initializing campaign...',
                'running': 'Sending messages...',
                'paused': 'Campaign paused',
                'stopped': 'Campaign stopped',
                'completed': 'Campaign completed successfully',
                'error': 'Campaign encountered an error'
            };
            return messages[status] || 'Processing campaign...';
        }

        function formatTime(seconds) {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;
            return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }

        function showAlert(message, type = 'info') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.innerHTML = `<strong>${type.charAt(0).toUpperCase() + type.slice(1)}:</strong> ${message}`;
            
            const container = document.querySelector('.container');
            container.insertBefore(alertDiv, container.firstChild);
            
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }

        function showLoading(message) {
            const loadingDiv = document.createElement('div');
            loadingDiv.id = 'loadingDiv';
            loadingDiv.className = 'alert alert-info';
            loadingDiv.innerHTML = `<strong>Loading:</strong> ${message}`;
            
            const container = document.querySelector('.container');
            container.insertBefore(loadingDiv, container.firstChild);
        }

        function hideLoading() {
            const loadingDiv = document.getElementById('loadingDiv');
            if (loadingDiv) {
                loadingDiv.remove();
            }
        }

        function showCompletionAlert() {
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success';
            alertDiv.innerHTML = '<strong>Campaign Complete!</strong> All messages have been processed. Check the logs below for detailed results.';
            
            const progressCard = document.getElementById('progressCard');
            progressCard.insertBefore(alertDiv, progressCard.firstChild);
            
            showNotification('Campaign completed successfully!', 'success');
        }
        
        
        
        
        // Utility Functions
        function createModal(title, content, buttons) {
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title">${title}</h2>
                        <span class="close">&times;</span>
                    </div>
                    <div class="modal-body">
                        ${content}
                    </div>
                    <div class="modal-footer">
                        ${buttons.map(btn => `<button class="btn ${btn.class}" data-action="${btn.action}" ${btn.data ? `data-id="${btn.data}"` : ''}>${btn.text}</button>`).join('')}
                    </div>
                </div>
            `;
            
            // Add event listeners
            modal.querySelector('.close').onclick = closeModal;
            modal.onclick = function(e) {
                if (e.target === modal) closeModal();
            };
            
            buttons.forEach(btn => {
                const button = modal.querySelector(`[data-action="${btn.action}"]`);
                if (button) {
                    button.onclick = function() {
                        if (btn.action === 'close') {
                            closeModal();
                        } else if (btn.action === 'saveTemplate') {
                            saveTemplate();
                        } else if (btn.action === 'updateTemplate') {
                            updateTemplate(btn.data);
                        } else if (btn.action === 'saveContact') {
                            saveContact();
                        } else if (btn.action === 'updateContact') {
                            updateContact(btn.data);
                        }
                    };
                }
            });
            
            return modal;
        }
        
        function closeModal() {
            const modal = document.querySelector('.modal');
            if (modal) {
                modal.remove();
            }
        }
        
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 5000);
        }
        
        function initializeNotifications() {
            // Request notification permission
            if ('Notification' in window && Notification.permission === 'default') {
                Notification.requestPermission();
            }
        }
        
        function parseCSV(csvText) {
            const lines = csvText.split('\n');
            const headers = lines[0].split(',').map(h => h.trim().toLowerCase());
            const contacts = [];
            
            for (let i = 1; i < lines.length; i++) {
                const values = lines[i].split(',');
                if (values.length >= headers.length) {
                    const contact = {};
                    headers.forEach((header, index) => {
                        contact[header] = values[index] ? values[index].trim() : '';
                    });
                    
                    if (contact.phone) {
                        contact.status = 'valid';
                        contacts.push(contact);
                    }
                }
            }
            
            return contacts;
        }
        
        function convertToCSV(data) {
            if (data.length === 0) return '';
            
            const headers = Object.keys(data[0]);
            const csvContent = [
                headers.join(','),
                ...data.map(row => headers.map(header => row[header] || '').join(','))
            ].join('\n');
            
            return csvContent;
        }
    </script>
</body>
</html>

