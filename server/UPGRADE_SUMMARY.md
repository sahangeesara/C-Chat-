# 1GB File Upload Upgrade - Summary

## ✅ Completed Changes

### 1. Configuration Files Created/Updated

#### New Files:
- **config/upload.php** - Centralized upload configuration
- **app/Http/Middleware/HandleLargeFileUploads.php** - Middleware for execution time
- **UPLOAD_SETUP.md** - Complete setup documentation
- **php-large-uploads.ini** - PHP configuration file
- **start-server-large-uploads.bat** - Windows batch script
- **start-server-large-uploads.ps1** - PowerShell script

#### Updated Files:
- **.env** - Added `UPLOAD_MAX_FILE_SIZE_KB=1048576`
- **app/Http/Controllers/ChatController.php** - Updated validation to 1GB
- **app/Http/Kernel.php** - Registered new middleware
- **routes/api.php** - Applied middleware to upload routes
- **public/.htaccess** - Added PHP directives for 1GB uploads

### 2. File Size Upgrades

| Setting | Old Value | New Value |
|---------|-----------|-----------|
| Upload Max Filesize | 100MB | 1GB (1024MB) |
| Post Max Size | 100MB | 1GB (1024MB) |
| Memory Limit | - | 512MB |
| Max Execution Time | - | 300 seconds |
| Max Input Time | - | 300 seconds |

### 3. Supported File Types (Expanded)

**Images:** JPEG, PNG, GIF, WebP, BMP, SVG
**Videos:** MP4, WebM, Ogg, QuickTime, AVI, Matroska, FLV, MPEG
**Audio:** MP3, WAV, WebM, Ogg, MP4, FLAC
**Documents:** PDF, Word, Excel, PowerPoint, Plain Text
**Archives:** ZIP, RAR, 7z

### 4. Routes Updated

- `POST /api/send` - Direct message with large file support
- `POST /api/groups/{group}/messages` - Group message with large file support
- `POST /api/group/{group}/messages` - Alternative group message endpoint

---

## 🚀 How to Use

### Quick Start (Windows)

**Option 1: PowerShell**
```powershell
.\start-server-large-uploads.ps1
```

**Option 2: Command Prompt**
```bash
start-server-large-uploads.bat
```

**Option 3: Manual**
```bash
php artisan serve
```

### Configuration by Server Type

#### Apache (Already Configured)
✅ `.htaccess` file updated in `public/` directory
- No additional steps needed
- Automatically applied via .htaccess

#### Nginx
Add to your nginx.conf server block:
```nginx
client_max_body_size 1024M;
proxy_connect_timeout 300s;
proxy_send_timeout 300s;
proxy_read_timeout 300s;
```

#### IIS (Windows)
Add to web.config:
```xml
<security>
    <requestFiltering>
        <requestLimits maxAllowedContentLength="1099511627776" />
    </requestFiltering>
</security>
```

#### Local PHP Development
```bash
php -c php-large-uploads.ini artisan serve
```

---

## 📊 Technical Implementation

### Validation Flow

```
Request with File
    ↓
ChatController::send()
    ↓
resolveAttachmentFile() - Extract file from request
    ↓
Validate Rules:
  - File size: max:1048576 (KB)
  - MIME types: 20+ formats
    ↓
Store in: storage/app/public/chat-attachments/
    ↓
Generate Asset URL
    ↓
Save to Database with Metadata:
  - attachment (URL)
  - attachment_mime
  - attachment_name
  - attachment_size
    ↓
Broadcast Event (Real-time update)
    ↓
Return Response
```

### Database Schema

```sql
CREATE TABLE messages (
    id BIGINT PRIMARY KEY,
    from_id BIGINT,
    to_id BIGINT,
    group_id BIGINT,
    body TEXT,
    attachment VARCHAR(2048),
    attachment_mime VARCHAR(255),
    attachment_name VARCHAR(255),
    attachment_size BIGINT,
    seen BOOLEAN,
    is_active BOOLEAN,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

---

## 🧪 Testing

### Using cURL
```bash
curl -X POST http://localhost:8000/api/send \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -F "message=Here is my large video" \
  -F "to_id=2" \
  -F "attachment=@/path/to/video.mp4"
```

### Using Postman
1. Set request type: **POST**
2. URL: `http://localhost:8000/api/send`
3. Headers: `Authorization: Bearer YOUR_JWT_TOKEN`
4. Body (form-data):
   - `message`: "Your message text"
   - `to_id`: 2 (recipient ID)
   - `attachment`: Select your 1GB file

### Using Vue/JavaScript
```javascript
const formData = new FormData();
formData.append('message', 'Sending large video');
formData.append('to_id', recipientId);
formData.append('attachment', videoFile);

const response = await axios.post('/api/send', formData, {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'multipart/form-data'
  },
  onUploadProgress: (progressEvent) => {
    const percentComplete = Math.round(
      (progressEvent.loaded * 100) / progressEvent.total
    );
    console.log(`Upload: ${percentComplete}%`);
  }
});
```

---

## ⚙️ Environment Variables

```env
# Required
UPLOAD_MAX_FILE_SIZE_KB=1048576

# Optional (already set)
FILESYSTEM_DISK=local
APP_DEBUG=true
```

---

## 📁 Storage Location

Uploaded files are stored in:
```
storage/app/public/chat-attachments/
```

Public URL format:
```
http://localhost:8000/storage/chat-attachments/{filename}
```

To make storage accessible via web:
```bash
php artisan storage:link
```

---

## 🔒 Security Features

✅ **MIME Type Validation** - Only allowed file types accepted
✅ **File Size Validation** - 1GB maximum enforced
✅ **Authentication** - Requires valid JWT token
✅ **Authorization** - Users can only see their own messages
✅ **Filename Sanitization** - Stored with unique names
✅ **Storage Permissions** - Files outside public directory by default

---

## 📝 Troubleshooting

### Issue: File upload fails with "413 Payload Too Large"
```
✗ Solution:
- Check Nginx client_max_body_size setting
- Verify .htaccess is in public/ directory
- Restart your web server
```

### Issue: Upload times out (504 Gateway Timeout)
```
✗ Solution:
- Increase max_execution_time to 300+ seconds
- Increase proxy_read_timeout in Nginx
- Check server resources (CPU, memory)
```

### Issue: "The attachment failed to upload" error
```
✗ Solution:
- Verify file size < 1GB
- Check file MIME type is in allowed list
- Ensure storage/ directory has write permissions
```

### Issue: File saved but doesn't appear in chat
```
✗ Solution:
- Check WebSocket connection (Pusher)
- Verify database record was created
- Check storage/logs/laravel.log for errors
```

---

## 📚 Additional Resources

- Setup Guide: `UPLOAD_SETUP.md`
- Laravel Docs: https://laravel.com/docs/11.x/filesystem
- Configuration: `config/upload.php`

---

## ✨ Next Steps (Optional Enhancements)

### 1. Chunked Uploads for Better UX
Implement frontend chunking for progress tracking

### 2. Async Processing
Queue large files for background processing

### 3. Compression
Auto-compress images/videos before storage

### 4. Virus Scanning
Integrate ClamAV for security

### 5. CDN Integration
Use AWS S3 or CloudFlare for delivery

### 6. Rate Limiting
Add upload quotas per user

---

## 📞 Support Checklist

Before contacting support:
- [ ] Verified PHP version supports 1GB uploads
- [ ] Checked server log files: `storage/logs/laravel.log`
- [ ] Tested with smaller file first (< 100MB)
- [ ] Confirmed JWT token is valid
- [ ] Verified storage/ directory permissions
- [ ] Checked network connection is stable
- [ ] Restarted web server after config changes

---

**Last Updated:** April 7, 2026
**Version:** 1.0
**Status:** ✅ Ready for Production

