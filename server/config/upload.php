<?php

return [
    /*
    |--------------------------------------------------------------------------
    | File Upload Configuration
    |--------------------------------------------------------------------------
    |
    | Configure maximum file sizes and MIME types for different file categories.
    |
    */

    // Global fallback max file size in kilobytes (1GB = 1048576 KB)
    'max_file_size_kb' => env('UPLOAD_MAX_FILE_SIZE_KB', 1048576),

    // Per-category max size overrides (KB)
    'max_image_size_kb' => env('UPLOAD_MAX_IMAGE_SIZE_KB', 40960),
    'max_document_size_kb' => env('UPLOAD_MAX_DOCUMENT_SIZE_KB', 10240),

    // Maximum file size in bytes (1GB = 1073741824 bytes)
    'max_file_size_bytes' => env('UPLOAD_MAX_FILE_SIZE_KB', 1048576) * 1024,

    // File size for validation (Laravel validation uses KB)
    'max_file_size_validation' => env('UPLOAD_MAX_FILE_SIZE_KB', 1048576),

    'mimetypes' => [
        'image' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/bmp',
            'image/svg+xml',
        ],
        'video' => [
            'video/mp4',
            'video/webm',
            'video/ogg',
            'video/quicktime',
            'video/x-msvideo',
            'video/x-matroska',
            'video/x-flv',
            'video/mpeg',
        ],
        'audio' => [
            'audio/mpeg',
            'audio/mp3',
            'audio/wav',
            'audio/webm',
            'audio/ogg',
            'audio/mp4',
            'audio/flac',
        ],
        'document' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
        ],
        'archive' => [
            'application/zip',
            'application/x-zip-compressed',
            'application/x-rar-compressed',
            'application/x-7z-compressed',
        ],
    ],

    'allowed_mimetypes' => [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/bmp',
        'image/svg+xml',
        'video/mp4',
        'video/webm',
        'video/ogg',
        'video/quicktime',
        'video/x-msvideo',
        'video/x-matroska',
        'video/x-flv',
        'video/mpeg',
        'audio/mpeg',
        'audio/mp3',
        'audio/wav',
        'audio/webm',
        'audio/ogg',
        'audio/mp4',
        'audio/flac',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'application/zip',
        'application/x-zip-compressed',
        'application/x-rar-compressed',
        'application/x-7z-compressed',
    ],
];
