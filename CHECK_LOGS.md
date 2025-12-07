# How to Check Logs for Debugging

## Error Log Locations

### 1. Application Error Log (Recommended)
**Location**: `logs/php_errors.log`

This file contains all PHP errors and exceptions from `process.php`:
```bash
# View the log file
cat logs/php_errors.log

# Or on Windows
type logs\php_errors.log

# View last 50 lines
tail -n 50 logs/php_errors.log
```

### 2. PHP Error Log
**Location**: Check your PHP configuration

Find PHP error log location:
```php
<?php
echo ini_get('error_log');
?>
```

Common locations:
- Linux: `/var/log/php_errors.log` or `/var/log/apache2/error.log`
- Windows/XAMPP: `C:\xampp\php\logs\php_error_log`
- cPanel: Usually in `~/logs/error_log` or `~/public_html/error_log`

### 3. Apache/Nginx Error Log
**Location**: Server configuration

- Apache: Usually in `/var/log/apache2/error.log` or `C:\xampp\apache\logs\error.log`
- Nginx: Usually in `/var/log/nginx/error.log`

### 4. Campaign Logs
**Location**: `logs/sms_campaign_*.log`

These contain SMS sending logs:
```bash
# List all campaign logs
ls -la logs/sms_campaign_*.log

# View latest log
ls -t logs/sms_campaign_*.log | head -1 | xargs cat
```

## Debug Mode

To see detailed error messages in the API response, add `?debug=1` to your request:

```
POST process.php?debug=1
```

Or add `debug=1` to your form data when making the preview request.

## Common Issues on Live Server

### 1. File Permissions
```bash
# Ensure logs directory is writable
chmod 755 logs/
chmod 666 logs/php_errors.log
```

### 2. Directory Not Created
Check if directories exist:
```bash
ls -la logs/
ls -la uploads/
ls -la temp/
```

### 3. Missing PHP Extensions
Check if required extensions are installed:
```php
<?php
var_dump(extension_loaded('curl'));
var_dump(extension_loaded('mbstring'));
?>
```

### 4. Path Issues
On live servers, paths might be different. Check:
- `__DIR__` resolves correctly
- File paths are absolute or relative correctly
- Upload directory permissions

## Quick Debug Steps

1. **Check the application error log first**:
   ```bash
   tail -f logs/php_errors.log
   ```

2. **Enable debug mode** in your request:
   Add `?debug=1` to see detailed errors in JSON response

3. **Check PHP error log**:
   ```bash
   tail -f /var/log/php_errors.log
   # or
   tail -f C:\xampp\php\logs\php_error_log
   ```

4. **Check server error log**:
   ```bash
   tail -f /var/log/apache2/error.log
   # or
   tail -f C:\xampp\apache\logs\error.log
   ```

## Viewing Logs via Browser (if allowed)

You can create a simple log viewer (for development only):

```php
<?php
// log_viewer.php (DELETE IN PRODUCTION!)
header('Content-Type: text/plain');
readfile(__DIR__ . '/logs/php_errors.log');
?>
```

**WARNING**: Never expose this in production! It contains sensitive information.

