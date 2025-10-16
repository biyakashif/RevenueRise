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
        'order_reward',
        'last_profit_reset',
        'force_lucky_order', // Added
        'withdraw_limit',
        'tasks_auto_reset',
        'referral_percentage',
    ];

    protected $hidden = [
        'remember_token',
    ];

    protected $casts = [
        'balance' => 'float',
        'todays_profit' => 'float',
        'order_reward' => 'float',
        'last_profit_reset' => 'date',
        'force_lucky_order' => 'boolean', // Added
        'withdraw_limit' => 'float',
        'tasks_auto_reset' => 'boolean',
        'referral_percentage' => 'float',
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
        return $this->hasMany(Deposit::class);
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



    protected static function booted()
    {
        // Removed auto-assign tasks on user creation
        // static::created(function ($user) {
        //     $user->assignTasks();
        // });
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