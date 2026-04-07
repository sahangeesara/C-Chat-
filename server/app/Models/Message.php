<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'from_id',
        'to_id',
        'group_id',
        'body',
        'attachment',
        'attachment_mime',
        'attachment_name',
        'attachment_size',
        'seen',
        'is_active',
    ];

    protected $casts = [
        'attachment_size' => 'integer',
    ];

    protected $hidden = ['user'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }
}
