<?php

namespace App\Models;

use Database\Factories\MessageDeliveryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageDelivery extends Model
{
    /** @use HasFactory<MessageDeliveryFactory> */
    use HasFactory;

    protected $fillable = ['announcement_id', 'school_id', 'guardian_user_id', 'channel', 'status', 'delivered_at', 'read_at'];

    protected function casts(): array
    {
        return ['delivered_at' => 'datetime', 'read_at' => 'datetime'];
    }

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guardian_user_id');
    }
}
