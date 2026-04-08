<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Call extends Model
{
    use HasFactory;

    protected $fillable = [
        'caller_id',
        'callee_id',
        'group_id',
        'status',
        'call_type',
        'offer',
        'answer_sdp',
        'participants',
        'ended_by',
        'end_reason',
        'started_at',
        'answered_at',
        'ended_at',
    ];

    protected $casts = [
        'offer' => 'array',
        'answer_sdp' => 'array',
        'participants' => 'array',
        'call_type' => 'string',
        'started_at' => 'datetime',
        'answered_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function caller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'caller_id');
    }

    public function callee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'callee_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function endedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ended_by');
    }
}

