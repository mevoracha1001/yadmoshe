# Yad Moshe - SMS Campaign Management System

A professional PHP-based SMS campaign management system with support for bulk SMS sending, MMS (image attachments), concurrent processing, and real-time progress tracking.

## Features

- 📱 **Bulk SMS Campaigns** - Send SMS messages to multiple recipients from CSV files
- 🖼️ **MMS Support** - Send images/photos along with text messages (each recipient receives a separate message)
- ⚡ **Concurrent Processing** - High-performance concurrent SMS sending with configurable batch sizes
- 📊 **Real-time Progress** - Live progress tracking with detailed statistics and console logs
- 🔍 **Message Preview** - Preview how messages will appear before sending
- 📝 **Template Variables** - Support for dynamic placeholders (@name, @id, @phone, @link)
- 🔐 **Secure Authentication** - Session-based authentication with Twilio credentials
- 📈 **Analytics & Logging** - Comprehensive logging and campaign statistics

## Requirements

- PHP 7.4 or higher
- cURL extension enabled
- Composer (for Twilio SDK)
- Web server (Apache/Nginx) or XAMPP/WAMP
- Twilio account with SMS/MMS enabled

## Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/YOUR_USERNAME/yadmoshe.git
   cd yadmoshe
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure the application**
   ```bash
   cp config.example.php config.php
   ```
   
   Edit `config.php` and add your Twilio credentials:
   - `TWILIO_USERNAME`: Your Twilio Account SID
   - `TWILIO_PASSWORD`: Your Twilio Auth Token

4. **Set up directories**
   The following directories will be created automatically:
   - `uploads/` - For CSV files and images
   - `uploads/images/` - For MMS image attachments
   - `logs/` - For campaign logs
   - `temp/` - For temporary process files

5. **Configure web server**
   - Point your web server document root to the project directory
   - Ensure PHP has write permissions to `uploads/`, `logs/`, and `temp/` directories

## Usage

### Sending SMS Campaigns

1. **Prepare your CSV file**
   - Format 1: CSV with headers (must include "phone" column)
     ```csv
     phone,name,id
     +1234567890,John Doe,123
     +0987654321,Jane Smith,456
     ```
   - Format 2: Simple list (one phone number per row)
     ```csv
     +1234567890
     +0987654321
     ```

2. **Access the application**
   - Navigate to `http://localhost/yadmoshe/` (or your server URL)
   - Login with your Twilio Account SID and Auth Token

3. **Create a campaign**
   - Upload your CSV file
   - Enter sender phone number (must be a Twilio number)
   - Write your message template with variables:
     - `@name` - Contact name
     - `@id` - Contact ID
     - `@phone` - Phone number
     - `@link` - Dynamic link (uses base URL + contact ID)
   - (Optional) Upload an image for MMS
   - Configure batch size and concurrent requests
   - Click "Preview Messages" to see how messages will appear
   - Click "Send SMS Campaign" to start

### Sending Images (MMS)

1. Upload an image file (JPEG, PNG, GIF, or WebP)
2. Maximum file size: 5MB
3. Each recipient will receive a **separate MMS message** with the image attachment
4. The image URL must be publicly accessible (Twilio needs to fetch it)

**Important**: For MMS to work, your server must be publicly accessible. If using localhost, consider:
- Deploying to a public server
- Using cloud storage (AWS S3, Google Cloud Storage) and providing that URL
- Using ngrok or similar service for temporary public access

## Configuration

### Concurrent Sending Settings

- **Batch Size**: Number of contacts processed in each batch (10-500)
- **Max Concurrent Requests**: Maximum simultaneous SMS requests (1-50)
- Recommended: Start with batch size 100 and max concurrent 25

### Message Template Variables

- `@name` - Replaced with contact's name from CSV
- `@id` - Replaced with contact's ID from CSV
- `@phone` - Replaced with contact's phone number
- `@link` - Replaced with: `{baseUrl}{contactId}`

Example template:
```
Hello @name! Your ID is @id. Visit: @link

Reply STOP to stop
```

## File Structure

```
yadmoshe/
├── config.php              # Configuration (create from config.example.php)
├── config.example.php      # Configuration template
├── index.php               # Main application interface
├── process.php             # SMS processing and API handlers
├── progress.php            # Progress tracking endpoint
├── twilio-client.php       # Twilio client functions
├── composer.json           # Composer dependencies
├── uploads/                # User uploads (CSV, images)
│   └── images/            # MMS image attachments
├── logs/                   # Campaign logs
├── temp/                   # Temporary process files
└── vendor/                 # Composer dependencies
```

## Security Notes

- **Never commit `config.php`** - It contains sensitive credentials
- The `.gitignore` file excludes sensitive files by default
- Use environment variables or secure credential storage in production
- Ensure proper file permissions on upload directories
- Consider implementing additional authentication for production use

## Troubleshooting

### Images not sending (MMS)
- Ensure your server is publicly accessible
- Check that image URLs are reachable from the internet
- Verify Twilio number supports MMS
- Check file size (max 5MB)

### SMS sending fails
- Verify Twilio credentials are correct
- Check sender number is a valid Twilio number
- Ensure phone numbers are in E.164 format (+1234567890)
- Check Twilio account balance

### Concurrent sending issues
- Reduce max concurrent requests if experiencing timeouts
- Increase PHP execution time limit
- Check server resources (memory, CPU)

## License

This project is open source and available for use.

## Support

For issues and questions, please open an issue on GitHub.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.







