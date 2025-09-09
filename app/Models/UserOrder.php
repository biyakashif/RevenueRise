<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_name',
        'mobile_number',
        'vip_level',
        'product_id',
        'task_name',
        'status',
        'order_number',
        'required_balance_to_confirm',
        'required_deposit_amount',
        'initial_balance',      
        'purchase_price',       
        'commission_reward',
    ];

    protected $casts = [
        'required_balance_to_confirm' => 'float',
        'required_deposit_amount' => 'float',
        'initial_balance' => 'float',
        'purchase_price' => 'float',
        'commission_reward' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}