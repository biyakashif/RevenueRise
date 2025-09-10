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
            \Log::info('Assigning tasks for user: ' . $this->id);

            // Fetch all available products
            $availableProducts = Product::all();

            // Get already assigned product IDs for this user
            $assignedProductIds = Task::where('user_id', $this->id)->pluck('product_id')->toArray();

            // Filter out already assigned products
            $newProducts = $availableProducts->whereNotIn('id', $assignedProductIds);

            // If no new products are available, allow repetition
            if ($newProducts->isEmpty()) {
                $newProducts = $availableProducts;
            }

            // Assign tasks with the new products
            $position = 1; // Start position counter
            foreach ($newProducts as $product) {
                Task::create([
                    'user_id' => $this->id,
                    'name' => 'Task for Product ' . $product->id, // Set a default name
                    'product_id' => $product->id,
                    'product_type' => $product->type,
                    'position' => $position++, // Increment position for each task
                ]);
            }

            \Log::info('Tasks assigned successfully for user: ' . $this->id);
        } catch (\Exception $e) {
            \Log::error('Error assigning tasks for user: ' . $this->id . ' - ' . $e->getMessage());
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