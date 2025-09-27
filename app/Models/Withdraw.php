<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Withdraw extends Model
{
    use HasFactory;

    protected $table = 'withdrawals';

    protected $fillable = [
        'user_id',
        'crypto_id',
        'crypto_symbol',
        'crypto_amount',
        'amount_withdraw',
        'status',
        'crypto_wallet',
        'approved_at',
        'rejected_at',
    ];

    protected $casts = [
        'amount_withdraw' => 'decimal:8',
        'crypto_amount' => 'decimal:8',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function crypto()
    {
        return $this->belongsTo(CryptoDepositDetail::class, 'crypto_id');
    }
}