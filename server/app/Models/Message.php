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
        'body',
        'attachment',
        'seen',
    ];
    protected $hidden = ['user'];
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class,'from_id');
    }
}
