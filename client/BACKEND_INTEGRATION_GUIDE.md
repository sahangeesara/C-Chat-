# Backend Integration Guide - File Attachment Handling

## Overview

The frontend has been updated to properly handle file uploads and downloads with correct filename preservation. This document provides guidance for backend developers to ensure compatibility.

## Expected Request Format

### When Sending Message with Attachment

The frontend sends a `FormData` POST request with the following fields:

```
POST /api/send
POST /api/messages  
POST /api/chat/send
POST /api/groups/{groupId}/messages
POST /api/group/{groupId}/messages
```

#### FormData Fields

| Field | Type | Example | Required |
|-------|------|---------|----------|
| `user_id` OR `to_id` | Number | 123 | ✅ (for direct messages) |
| `group_id` | Number | 456 | ✅ (for group messages) |
| `message` | String | "Check this document" | ❌ |
| `body` | String | "Check this document" | ❌ |
| `attachment` | File | [Binary Data] | ✅ |
| `attachment_name` | String | "report.pdf" | ✅ |
| `attachment_mime_type` | String | "application/pdf" | ✅ |
| `attachment_kind` | String | "document" | ✅ |
| `attachment_size` | String | "245632" | ✅ |
| `conversation_type` | String | "group" | ❌ |

#### Example cURL Request

```bash
curl -X POST http://localhost:8000/api/send \
  -H "Authorization: Bearer TOKEN" \
  -F "user_id=123" \
  -F "message=Here is my document" \
  -F "body=Here is my document" \
  -F "attachment=@/path/to/file.pdf" \
  -F "attachment_name=report.pdf" \
  -F "attachment_mime_type=application/pdf" \
  -F "attachment_kind=document" \
  -F "attachment_size=245632"
```

## Expected Response Format

### After Message is Stored

The backend should return the stored message with attachment metadata. Multiple response formats are supported:

#### Format 1: Direct Message Object
```json
{
  "id": 1,
  "from_id": 1,
  "to_id": 2,
  "body": "Here is my document",
  "created_at": "2026-04-07T10:30:00Z",
  "attachment_url": "http://storage/messages/file_123.pdf",
  "attachment_name": "report.pdf",
  "attachment_mime_type": "application/pdf",
  "attachment_kind": "document",
  "attachment_size": 245632
}
```

#### Format 2: Nested Message Object
```json
{
  "message": {
    "id": 1,
    "from_id": 1,
    "to_id": 2,
    "body": "Here is my document",
    "created_at": "2026-04-07T10:30:00Z",
    "attachment_url": "http://storage/messages/file_123.pdf",
    "attachment_name": "report.pdf",
    "attachment_mime_type": "application/pdf",
    "attachment_kind": "document",
    "attachment_size": 245632
  }
}
```

#### Format 3: Data Wrapper
```json
{
  "data": {
    "id": 1,
    "from_id": 1,
    "to_id": 2,
    "body": "Here is my document",
    "created_at": "2026-04-07T10:30:00Z",
    "attachment_url": "http://storage/messages/file_123.pdf",
    "attachment_name": "report.pdf",
    "attachment_mime_type": "application/pdf",
    "attachment_kind": "document",
    "attachment_size": 245632
  }
}
```

## Critical Fields for Receiver

When sending message data to the receiver (via WebSocket/realtime event), include all attachment fields:

```json
{
  "id": 1,
  "from_id": 1,
  "to_id": 2,
  "sender_name": "John Doe",
  "receiver_name": "Jane Smith",
  "body": "Check this file",
  "created_at": "2026-04-07T10:30:00Z",
  "attachment_url": "http://storage/messages/file_123.pdf",
  "attachment_name": "report.pdf",
  "attachment_mime_type": "application/pdf",
  "attachment_kind": "document",
  "attachment_size": 245632
}
```

## File Kind Classification

The frontend classifies files into types based on MIME type or filename:

| Kind | MIME Types | Extensions |
|------|-----------|-----------|
| `image` | `image/*` | .jpg, .png, .gif, .webp, etc. |
| `video` | `video/*` | .mp4, .webm, .mov, .avi, etc. |
| `audio` | `audio/*` | .mp3, .wav, .ogg, .m4a, etc. |
| `document` | Everything else | .pdf, .docx, .xlsx, .txt, etc. |

## URL Requirements

The `attachment_url` must be:
1. ✅ Publicly accessible (can be accessed without authentication)
2. ✅ HTTPS compatible (if frontend is HTTPS)
3. ✅ CORS enabled (if served from different domain)
4. ✅ Direct file URL (not a redirect endpoint)

## Storage Recommendations

### Best Practices

1. **Save with unique filename**: Prevent collisions
   ```
   /storage/messages/user_1_msg_12345_original_report.pdf
   ```

2. **Preserve original filename**: For download functionality
   ```json
   {
     "storage_path": "/messages/user_1_msg_12345_hash.pdf",
     "original_name": "report.pdf"
   }
   ```

3. **Store metadata**: For statistics and validation
   ```json
   {
     "file_size": 245632,
     "mime_type": "application/pdf",
     "upload_time": "2026-04-07T10:30:00Z"
   }
   ```

## Field Name Variations Supported

The frontend handles multiple field name variations for maximum compatibility:

### Attachment Name
- `attachment_name`
- `file_name`
- `original_name`
- `filename`

### Attachment URL
- `attachment_url`
- `file_url`
- `media_url`
- `attachment`
- `file`
- `media`
- `path`
- `file_path`
- `attachment_path`

### Attachment MIME Type
- `attachment_mime_type`
- `attachment_mime`
- `mime_type`
- `type`
- `content_type`

### Attachment Size
- `attachment_size`
- `file_size`

### Attachment Kind/Type
- `attachment_kind`
- `message_kind`
- `kind`

**Note**: Use the primary field names (first in each list) for best compatibility.

## Validation Requirements

### On Backend

1. **File Size Limit**: Define and enforce
   ```php
   $max_size = 100 * 1024 * 1024; // 100 MB
   if ($file->getSize() > $max_size) {
       return error('File too large');
   }
   ```

2. **File Type Validation**: Whitelist allowed types
   ```php
   $allowed = ['image/*', 'video/*', 'audio/*', 'application/pdf', ...];
   if (!isAllowedMimeType($file->getMimeType(), $allowed)) {
       return error('File type not allowed');
   }
   ```

3. **Filename Sanitization**: Prevent path traversal
   ```php
   $safe_name = preg_replace('/[^a-zA-Z0-9._\-]/', '_', $original_name);
   ```

4. **Virus Scanning**: Optional but recommended
   ```php
   if (!scanForViruses($file_path)) {
       return error('File contains malware');
   }
   ```

## WebSocket/Realtime Events

When broadcasting messages via WebSocket (e.g., Laravel Echo), include full attachment data:

```php
// Laravel Example
broadcast(new MessageSent(
    [
        'id' => $message->id,
        'from_id' => $message->from_id,
        'to_id' => $message->to_id,
        'body' => $message->body,
        'created_at' => $message->created_at,
        'attachment_url' => $message->attachment?->url,
        'attachment_name' => $message->attachment?->original_name,
        'attachment_mime_type' => $message->attachment?->mime_type,
        'attachment_kind' => $message->getAttachmentKind(),
        'attachment_size' => $message->attachment?->file_size,
    ]
));
```

## Testing Endpoints

### Test with File Upload

```bash
# Create test file
echo "Test content" > test_file.txt

# Send message with attachment
curl -X POST http://localhost:8000/api/send \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "to_id=2" \
  -F "message=Test message" \
  -F "attachment=@test_file.txt" \
  -F "attachment_name=test_file.txt" \
  -F "attachment_mime_type=text/plain" \
  -F "attachment_kind=document" \
  -F "attachment_size=13"

# Verify response includes all attachment fields
```

## Common Issues and Solutions

### Issue 1: Filename is Lost on Receiver Side
**Solution**: Ensure `attachment_name` is included in both response and WebSocket events

### Issue 2: Download Shows Wrong Filename
**Solution**: Verify `attachment_name` is set correctly and not null/empty

### Issue 3: File Can't Be Downloaded
**Solution**: Ensure `attachment_url` is publicly accessible and CORS headers are set

### Issue 4: Images/Videos Not Previewing
**Solution**: Verify `attachment_kind` is set correctly to "image" or "video"

### Issue 5: Document Icon Not Showing
**Solution**: Ensure `attachment_kind` is set for all file types, default should be "document"

## Migration Guide (If Updating Existing Code)

If you have an existing backend, ensure:

1. ✅ All message responses include attachment metadata
2. ✅ WebSocket events include attachment metadata
3. ✅ File URLs are publicly accessible
4. ✅ Original filenames are preserved and returned
5. ✅ MIME types are correctly set
6. ✅ File kind/type is determined and included

## Performance Considerations

1. **Lazy Load Large Files**: Use lazy loading for video/audio
2. **Optimize Images**: Compress and resize images before storage
3. **CDN Integration**: Serve files from CDN for better performance
4. **Caching**: Cache file metadata to reduce database queries
5. **Streaming**: For large files, implement range requests

## Security Considerations

1. ✅ Validate file types server-side (not just client-side)
2. ✅ Store files outside public web root if possible
3. ✅ Implement access control (only sender/receiver can access)
4. ✅ Scan files for malware
5. ✅ Rate limit file uploads
6. ✅ Sanitize filenames
7. ✅ Use HTTPS for file transmission

## Next Steps

1. Update message endpoints to include attachment metadata
2. Test with various file types and sizes
3. Implement file storage mechanism
4. Configure public URL access
5. Test with realtime events
6. Monitor file upload performance
7. Implement security measures

