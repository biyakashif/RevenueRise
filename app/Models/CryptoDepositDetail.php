<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CryptoDepositDetail extends Model
{
    use HasFactory;

    protected $fillable = ['symbol', 'qr_code', 'address', 'currency', 'network', 'is_active'];
    
    protected $casts = [
        'is_active' => 'boolean',
    ];
}