# 1GB Video File Upload Setup Guide

This document explains how to configure your Laravel ChatApp for 1GB file uploads.

## What's Been Updated

### 1. Laravel Configuration Files
- **config/upload.php** - New file with upload configuration
- **.env** - Added `UPLOAD_MAX_FILE_SIZE_KB=1048576` (1GB in KB)
- **app/Http/ChatController.php** - Updated file validation from 100MB to 1GB
- **app/Http/Kernel.php** - Added `HandleLargeFileUploads` middleware
- **routes/api.php** - Applied middleware to file upload routes

### 2. Server Configuration Files
- **public/.htaccess** - Added PHP directives for large uploads (Apache)
- **php-large-uploads.ini** - PHP config file for local development

---

## Setup Instructions

### Step 1: Update Laravel (Already Done)
The following changes have been applied automatically:
- ✅ ChatController.php updated to use 1GB limit
- ✅ New middleware created for handling execution time
- ✅ Routes updated with middleware

### Step 2: Configure Your Server

#### For **Apache** (Using .htaccess)
The .htaccess file already includes:
```apache
<IfModule mod_php.c>
    php_value upload_max_filesize 1024M
    php_value post_max_size 1024M
    php_value max_execution_time 300
    php_value max_input_time 300
    php_value memory_limit 512M
</IfModule>
```

**No additional action needed** - the settings are in `public/.htaccess`

---

#### For **Nginx**
Add to your server block in `nginx.conf`:
```nginx
server {
    # ... other config ...
    
    client_max_body_size 1024M;
    
    # For PHP-FPM, also set these timeouts
    proxy_connect_timeout 300s;
    proxy_send_timeout 300s;
    proxy_read_timeout 300s;
}
```

Then reload Nginx:
```bash
sudo systemctl reload nginx
```

---

#### For **IIS** (Windows)
Add to your `web.config` in the `system.webServer` section:
```xml
<security>
    <requestFiltering>
        <requestLimits maxAllowedContentLength="1099511627776" />
    </requestFiltering>
</security>
```

---

#### For **Local Development with PHP's Built-in Server**
Use the php.ini file provided:
```bash
php -c php-large-uploads.ini -S localhost:8000
```

Or modify your system's `php.ini` directly:
- Windows: `C:\php\php.ini`
- Linux: `/etc/php/X.X/apache2/php.ini` or `/etc/php/X.X/fpm/php.ini`
- macOS: `/usr/local/etc/php/X.X/php.ini`

Set these values:
```ini
upload_max_filesize = 1024M
post_max_size = 1024M
memory_limit = 512M
max_execution_time = 300
max_input_time = 300
```

---

### Step 3: Clear Laravel Cache
```bash
php artisan config:cache
php artisan view:clear
php artisan cache:clear
```

---

### Step 4: Storage Directory Permissions
Ensure storage directory is writable:
```bash
# Linux/macOS
chmod -R 775 storage/
chown -R www-data:www-data storage/  # or your web server user

# Windows (via PowerShell as Admin)
icacls "storage" /grant:r "IIS_IUSRS:(OI)(CI)(F)" /T
```

---

## Supported File Types

The upload now supports:

### Images
- JPEG, PNG, GIF, WebP, BMP, SVG

### Videos
- MP4, WebM, Ogg, QuickTime, AVI, Matroska, FLV, MPEG

### Audio
- MP3, WAV, WebM, Ogg, MP4, FLAC

### Documents
- PDF, Word (.docx), Excel (.xlsx), PowerPoint (.pptx), Plain Text

### Archives
- ZIP, RAR, 7z

---

## Database Schema

The message table already has the following columns for file metadata:
- `attachment` (string) - File URL
- `attachment_mime` (string) - MIME type
- `attachment_name` (string) - Original filename
- `attachment_size` (bigint) - File size in bytes

Run pending migrations if needed:
```bash
php artisan migrate
```

---

## Testing Large Uploads

### Test with cURL
```bash
curl -X POST http://localhost:8000/api/send \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "message=Test message" \
  -F "to_id=2" \
  -F "attachment=@large-video.mp4"
```

### Test with Python
```python
import requests

url = 'http://localhost:8000/api/send'
headers = {'Authorization': 'Bearer YOUR_TOKEN'}
files = {'attachment': open('large-video.mp4', 'rb')}
data = {
    'message': 'Sending large video',
    'to_id': 2
}

response = requests.post(url, headers=headers, files=files, data=data)
print(response.json())
```

---

## Performance Optimization Tips

### 1. **Chunk Uploads for Frontend**
For very large files, implement chunked uploads in Vue:
```javascript
// Pseudo-code
const chunkSize = 10 * 1024 * 1024; // 10MB chunks
const totalChunks = Math.ceil(file.size / chunkSize);

for (let i = 0; i < totalChunks; i++) {
  const start = i * chunkSize;
  const end = Math.min(start + chunkSize, file.size);
  const chunk = file.slice(start, end);
  
  const formData = new FormData();
  formData.append('chunk', chunk);
  formData.append('chunkIndex', i);
  formData.append('totalChunks', totalChunks);
  
  await axios.post('/api/send', formData);
}
```

### 2. **Async Processing**
Process large uploads asynchronously:
```php
// In ChatController
if ($attachmentSize > 500 * 1024 * 1024) { // > 500MB
    // Queue for async processing
    ProcessLargeFileUpload::dispatch($message);
}
```

### 3. **Storage Location**
For production, consider using:
- Dedicated storage drives/partitions
- Cloud storage (AWS S3, Azure Blob, etc.)
- CDN for file delivery

---

## Troubleshooting

### Error: "The attachment failed to upload"
**Cause:** File exceeds PHP limits
**Solution:** Check your server's `php.ini` settings

### Error: "413 Request Entity Too Large"
**Cause:** Nginx `client_max_body_size` too small
**Solution:** Update nginx.conf and reload

### Error: "504 Gateway Timeout"
**Cause:** Upload takes too long
**Solution:** Increase `max_execution_time` and `proxy_read_timeout`

### Error: "Disk space full"
**Cause:** Storage partition out of space
**Solution:** Clean old files or expand storage

---

## Environment Variables Summary

```env
# File Upload Configuration
UPLOAD_MAX_FILE_SIZE_KB=1048576  # 1GB
FILESYSTEM_DISK=local            # Where to store files
APP_DEBUG=true                   # Enable for debugging uploads
```

---

## References

- [Laravel File Storage](https://laravel.com/docs/11.x/filesystem)
- [Nginx Upload Configuration](https://nginx.org/en/docs/http/ngx_http_core_module.html#client_max_body_size)
- [Apache mod_php](https://www.php.net/manual/en/ini.core.php)
- [IIS Upload Limits](https://docs.microsoft.com/en-us/iis/configuration/system.webserver/security/requestfiltering/requestlimits/)

---

## Security Considerations

1. **Virus Scanning** - Consider integrating ClamAV for uploaded files
2. **MIME Type Validation** - Currently done via `mimetypes` validation
3. **File Size Limits** - Per-user quotas can be added if needed
4. **Storage Permissions** - Files stored outside web root for security
5. **Rate Limiting** - Consider adding upload rate limits

---

## Support

For issues or questions:
1. Check Laravel logs: `storage/logs/`
2. Check Nginx/Apache error logs
3. Test with smaller files first
4. Verify all configuration changes were applied

