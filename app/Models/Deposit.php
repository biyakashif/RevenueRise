<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Deposit extends Model
{
    protected $fillable = [
        'user_id',
        'symbol',
        'network',
        'amount',
        'address',
        'qr_code',
        'slip_path',
        'status',
        'title',
        'vip_level',
    ];

    protected $casts = [
        'amount' => 'decimal:8',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}