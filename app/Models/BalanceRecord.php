<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BalanceRecord extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'from_user_name',
        'from_mobile_number',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
