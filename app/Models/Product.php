<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';

    protected $fillable = [
        'product_id',
        'title',
        'description',
        'type',
        'purchase_price',
        'selling_price',
        'commission_reward',
        'commission_percentage',
        'image_path',
    ];

    protected $casts = [
        'type' => 'string',
    ];
}