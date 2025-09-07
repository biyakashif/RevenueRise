<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Carbon\Carbon;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'id';
    protected $keyType = 'int';
    public $incrementing = true;

    protected $fillable = [
        'name',
        'mobile_number',
        'password',
        'withdraw_password',
        'invitation_code',
        'balance',
        'role',
        'referred_by',
        'vip_level',
        'avatar_url',
        'todays_profit',
        'last_profit_reset',
        'force_lucky_order', // Added
        'withdraw_limit',
    ];

    protected $hidden = [
        'remember_token',
    ];

    protected $casts = [
        'balance' => 'float',
        'todays_profit' => 'float',
        'last_profit_reset' => 'date',
        'force_lucky_order' => 'boolean', // Added
        'withdraw_limit' => 'float',
    ];

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = $value;
    }

    public function getAuthIdentifierName()
    {
        return 'id';
    }

    public function getAuthIdentifier()
    {
        return $this->id;
    }

    public function deposits()
    {
        return $this->hasMany(Deposit::class, 'user_id', 'mobile_number');
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'referred_by', 'invitation_code');
    }

    public function referrals()
    {
        return $this->hasMany(User::class, 'referred_by', 'invitation_code');
    }

    public function orders()
    {
        return $this->hasMany(UserOrder::class, 'user_id', 'id');
    }

    public function tasks()
    {
        return $this->hasMany(\App\Models\Task::class, 'user_id');
    }

    public function withdraws()
    {
        return $this->hasMany(Withdraw::class, 'user_id', 'id');
    }

    public function resetTodaysProfitIfNeeded()
    {
        $today = Carbon::today();
        if (!$this->last_profit_reset || $this->last_profit_reset->lt($today)) {
            $this->todays_profit = 0.00;
            $this->last_profit_reset = $today;
            $this->save();
        }
    }

    public function assignTasks()
    {
        try {
            // Remove old tasks for this user
            \App\Models\Task::where('user_id', $this->id)->delete();

            if ($this->vip_level === 'VIP1') {
                // Assign 40 random VIP1 products if they exist
                $products = \App\Models\Product::where('type', 'VIP1')->inRandomOrder()->limit(40)->get();
                if ($products->isNotEmpty()) {
                    foreach ($products as $index => $product) {
                        \App\Models\Task::create([
                            'user_id' => $this->id,
                            'name' => $this->vip_level,
                            'product_id' => $product->id,
                            'product_type' => 'VIP1',
                            'position' => $index + 1,
                        ]);
                    }
                }
            } else {
                // Assign 36 VIPs and 4 Lucky Order products if they exist
                $vips = \App\Models\Product::where('type', 'VIPs')->inRandomOrder()->limit(36)->get();
                $lucky = \App\Models\Product::where('type', 'Lucky Order')->inRandomOrder()->limit(4)->get();

                if ($vips->isNotEmpty() || $lucky->isNotEmpty()) {
                    $tasks = [];
                    $vipsIndex = 0;
                    $luckyIndex = 0;
                    for ($i = 1; $i <= 40; $i++) {
                        if ($i % 10 === 0 && $luckyIndex < $lucky->count()) {
                            $tasks[] = [
                                'user_id' => $this->id,
                                'name' => $this->vip_level,
                                'product_id' => $lucky[$luckyIndex]->id,
                                'product_type' => 'Lucky Order',
                                'position' => $i,
                            ];
                            $luckyIndex++;
                        } else if ($vipsIndex < $vips->count()) {
                            $tasks[] = [
                                'user_id' => $this->id,
                                'name' => $this->vip_level,
                                'product_id' => $vips[$vipsIndex]->id,
                                'product_type' => 'VIPs',
                                'position' => $i,
                            ];
                            $vipsIndex++;
                        }
                    }
                    if (!empty($tasks)) {
                        \App\Models\Task::insert($tasks);
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error in assignTasks: ' . $e->getMessage());
        }
    }

    protected static function booted()
    {
        static::created(function ($user) {
            $user->assignTasks();
        });
    }

    public function unreadMessages()
    {
        return $this->hasMany(ChatMessage::class, 'recipient_id')
                    ->whereNull('read_at');
    }

    public function sentMessages()
    {
        return $this->hasMany(ChatMessage::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(ChatMessage::class, 'recipient_id');
    }
}
?>