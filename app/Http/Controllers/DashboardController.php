<?php

namespace App\Http\Controllers;

use App\Models\SliderImage;
use App\Models\UserOrder;
use App\Models\Task;
use App\Models\Product;
use App\Models\BalanceRecord;
use App\Events\OrderConfirmed;
use Inertia\Response;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(): mixed
    {
        $user = auth()->user();
        if ($user && $user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        if ($user) {
            $user->resetTodaysProfitIfNeeded();
        }

        // Get active slider images
        $desktopSliders = SliderImage::desktop()->active()->ordered()->get();
        $mobileSliders = SliderImage::mobile()->active()->ordered()->get();

        return Inertia::render('Dashboard', [
            'user' => $user ? $user->only(['id', 'mobile_number', 'invitation_code', 'balance', 'vip_level', 'frozen_balance', 'todays_profit']) : null,
            'desktopSliders' => $desktopSliders,
            'mobileSliders' => $mobileSliders,
        ]);
    }

    public function orders(Request $request): Response
    {
        try {
            $user = $request->user();
            if (!$user) {
                return redirect()->route('login')->with('error', 'Please log in to view orders.');
            }

            $user->resetTodaysProfitIfNeeded();

            $confirmedProductIds = UserOrder::where('user_id', $user->id)
                ->where('status', 'confirmed')
                ->pluck('product_id');

            $taskTotalCount = Task::where('user_id', $user->id)->count();

            $confirmedCount = UserOrder::where('user_id', $user->id)
                ->where('status', 'confirmed')
                ->count();

            $taskModels = Task::with('product')
                ->where('user_id', $user->id)
                ->orderBy('position')
                ->orderBy('id')
                ->get();

            $tasksForResponse = $taskModels->map(function ($task) use ($user) {
                $order = UserOrder::where('user_id', $user->id)
                    ->where('product_id', $task->product_id)
                    ->where('order_number', $task->position)
                    ->first();
                return [
                    'id' => $task['id'] ?? null,
                    'name' => $task['name'],
                    'product_id' => $task['product_id'],
                    'product_type' => $task['product_type'],
                    'position' => $task['position'] ?? 0,
                    'status' => $order ? $order->status : 'confirmed',
                    'product' => $task['product'] ? [
                        'id' => $task['product']['id'],
                        'product_id' => $task['product']['product_id'],
                        'title' => $task['product']['title'],
                        'description' => $task['product']['description'],
                        'purchase_price' => $task['product']['purchase_price'],
                        'selling_price' => $task['product']['selling_price'],
                        'commission_reward' => $task['product']['commission_reward'],
                        'commission_percentage' => $task['product']['commission_percentage'],
                        'image_path' => $task['product']['image_path'],
                        'type' => $task['product']['type'],
                    ] : null,
                ];
            })->toArray();

            if ($user->force_lucky_order) {
                $luckyProduct = Product::where('type', 'Lucky Order')
                    ->whereNotIn('id', $confirmedProductIds)
                    ->inRandomOrder()
                    ->first();

                if ($luckyProduct) {
                    $forcedTask = [
                        'id' => null,
                        'name' => null,
                        'product_id' => $luckyProduct->id,
                        'product_type' => 'Lucky Order',
                        'position' => null,
                        'status' => 'confirmed',
                        'product' => [
                            'is_forced' => true,
                        ],
                        'forced_lucky' => true,
                    ];

                    $insertIndex = (int) max(0, min($confirmedCount, count($tasksForResponse)));
                    array_splice($tasksForResponse, $insertIndex, 0, [$forcedTask]);
                    $taskTotalCount = $taskTotalCount + 1;
                } else {
                    return Inertia::render('Orders', [
                        'products' => [],
                        'currentProductIndex' => 0,
                        'user' => $user->only(['id', 'name', 'balance', 'frozen_balance', 'vip_level', 'mobile_number', 'todays_profit']),
                        'tasks' => [],
                        'taskTotalCount' => $taskTotalCount,
                        'confirmedCount' => $confirmedCount,
                        'flash' => ['error' => 'No Lucky Order products available.'],
                    ]);
                }
            }

            $products = Product::whereNotIn('id', function ($query) use ($user) {
                    $query->select('product_id')
                          ->from('user_orders')
                          ->where('user_id', $user->id)
                          ->where('status', 'confirmed');
                })
                ->latest()
                ->get();

            $currentIndex = (int) $request->input('current_product_index', 0);

            return Inertia::render('Orders', [
                'products' => $products,
                'currentProductIndex' => $currentIndex,
                'user' => $user->only(['id', 'name', 'balance', 'frozen_balance', 'vip_level', 'mobile_number', 'todays_profit', 'order_reward']),
                'tasks' => collect($tasksForResponse),
                'taskTotalCount' => $taskTotalCount,
                'confirmedCount' => $confirmedCount,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error loading orders: ' . $e->getMessage(), ['exception' => $e]);
            return Inertia::render('Orders', [
                'products' => [],
                'currentProductIndex' => 0,
                'user' => [],
                'tasks' => [],
                'taskTotalCount' => 0,
                'confirmedCount' => 0,
                'flash' => ['error' => 'An error occurred while loading orders. Please try again.'],
            ]);
        }
    }

    public function storeOrder(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'task_name' => 'required|string',
            'is_forced' => 'sometimes|boolean',
        ]);

        return DB::transaction(function () use ($request) {
            $user = Auth::user();
            $product = Product::findOrFail($request->product_id);
            $isForced = $request->input('is_forced', false);

            $today = now()->toDateString();
            $nextOrderNumber = (int) (
                UserOrder::where('user_id', $user->id)
                    ->where('task_name', $request->task_name)
                    ->whereDate('created_at', $today)
                    ->max('order_number') ?? 0
            ) + 1;

            // Genuine Lucky Order Logic: ONLY if the type is 'Lucky Order' AND it's NOT forced.
            if ($product->type === 'Lucky Order' && !$isForced) {
                $user->refresh();
                if ($user->balance < 0) {
                    return back()->with('error', 'You do not have sufficient balance to confirm this order.');
                }
                
                $existingOrder = UserOrder::where('user_id', $user->id)
                    ->where('product_id', $request->product_id)
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->first();
                
                if ($existingOrder && $existingOrder->status === 'pending') {
                    // Update existing pending order to confirmed
                    $existingOrder->status = 'confirmed';
                    $existingOrder->order_number = $nextOrderNumber;
                    $existingOrder->save();
                    \Log::info('Broadcasting OrderConfirmed event', ['order_id' => $existingOrder->id, 'user_id' => $existingOrder->user_id, 'product_id' => $existingOrder->product_id]);
                    event(new OrderConfirmed($existingOrder));
                } else {
                    // This case should ideally not happen if checkBalance is always called first
                    // But as a fallback, create the order record.
                    $order = UserOrder::create([
                        'user_id' => $user->id, 'user_name' => $user->name, 'mobile_number' => $user->mobile_number,
                        'vip_level' => $user->vip_level, 'product_id' => $request->product_id, 'task_name' => $request->task_name,
                        'status' => 'confirmed', 'order_number' => $nextOrderNumber, 'initial_balance' => $user->getOriginal('balance'),
                        'purchase_price' => $product->selling_price, 'commission_reward' => $product->commission_reward,
                    ]);
                    \Log::info('Broadcasting OrderConfirmed event', ['order_id' => $order->id, 'user_id' => $order->user_id, 'product_id' => $order->product_id]);
                    event(new OrderConfirmed($order));
                }
                // Add back selling_price + commission_reward for the genuine Lucky Order
                $user->balance += ($product->selling_price + $product->commission_reward);
                $user->todays_profit += $product->commission_reward; // Keep existing todays_profit logic
                $user->order_reward += $product->commission_reward; // Add to order_reward as well
                $user->save();
                \Log::info('Genuine Lucky Order confirmed, balance restored', [
                    'user_id' => $user->id, 'product_id' => $product->id, 'new_balance' => $user->balance,
                ]);
            } else {
                // Regular Order and Forced Lucky Order Logic
                $user->balance += ($product->commission_reward + $product->commission_reward);
                $user->todays_profit += $product->commission_reward; // Keep existing todays_profit logic
                $user->order_reward += $product->commission_reward; // Add to order_reward as well
                $user->save();
                $order = UserOrder::create([
                    'user_id' => $user->id, 'user_name' => $user->name, 'mobile_number' => $user->mobile_number,
                    'vip_level' => $user->vip_level, 'product_id' => $request->product_id, 'task_name' => $request->task_name,
                    'status' => 'confirmed', 'order_number' => $nextOrderNumber, 'initial_balance' => $user->getOriginal('balance'),
                    'purchase_price' => $product->selling_price, 'commission_reward' => $product->commission_reward,
                ]);
                \Log::info('Broadcasting OrderConfirmed event', ['order_id' => $order->id, 'user_id' => $order->user_id, 'product_id' => $order->product_id]);
                event(new OrderConfirmed($order));
                 \Log::info('Regular/Forced Order confirmed, commission rewarded.', [
                    'user_id' => $user->id, 'product_id' => $product->id, 'new_balance' => $user->balance,
                ]);
            }

            \Log::info('Order saved successfully', [
                'user_id' => $user->id,
                'product_id' => $request->product_id,
                'order_number' => $nextOrderNumber,
            ]);

            // Check if tasks are completed and add referral bonus
            $taskTotalCount = Task::where('user_id', $user->id)->count();
            $confirmedCount = UserOrder::where('user_id', $user->id)->where('status', 'confirmed')->count();
            if ($confirmedCount == $taskTotalCount && $taskTotalCount > 0) {
                $referrer = $user->inviter;
                if ($referrer) {
                    $bonus = $user->order_reward * ($referrer->referral_percentage / 100);
                    $referrer->balance += $bonus;
                    $referrer->save();
                    
                    // Create balance record for task completion bonus
                    BalanceRecord::create([
                        'user_id' => $referrer->id,
                        'type' => 'task_completion',
                        'amount' => $bonus,
                        'from_user_name' => $user->name,
                        'from_mobile_number' => $user->mobile_number,
                        'description' => 'Task completion bonus from referred user',
                    ]);
                    
                    \Log::info('Referral bonus added', [
                        'referrer_id' => $referrer->id,
                        'user_id' => $user->id,
                        'bonus' => $bonus,
                    ]);
                }
                // Keep order_reward displayed until admin reset or midnight auto reset
                // $user->order_reward = 0.00;
                // $user->save();
            }

            return back()->with('success', 'Order saved successfully.');
        }, 5);
    }

    public function checkBalance(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'is_forced' => 'sometimes|boolean',
        ]);

        $user = Auth::user();
        $product = Product::findOrFail($request->product_id);
        $isForced = $request->input('is_forced', false);

        return DB::transaction(function () use ($user, $product, $request, $isForced) {
            $user->refresh();

            \Log::info('Balance check', [
                'user_id' => $user->id,
                'balance' => $user->balance,
                'commission_reward' => $product->commission_reward,
                'product_id' => $product->id,
                'product_type' => $product->type,
                'selling_price' => $product->selling_price,
                'is_forced' => $isForced,
            ]);

            $existingOrder = UserOrder::where('user_id', $user->id)
                ->where('product_id', $product->id)
                ->whereIn('status', ['pending', 'confirmed'])
                ->first();

            // Genuine Lucky Order Logic: ONLY if the type is 'Lucky Order' AND it's NOT forced.
            if ($product->type === 'Lucky Order' && !$isForced) {
                if (!$existingOrder) {
                    // Create a pending order for the genuine Lucky Order
                    $today = now()->toDateString();
                    $nextOrderNumber = (int) (
                        UserOrder::where('user_id', $user->id)
                            ->where('task_name', $request->task_name ?? 'VIP1')
                            ->whereDate('created_at', $today)
                            ->max('order_number') ?? 0
                    ) + 1;

                    UserOrder::create([
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'mobile_number' => $user->mobile_number,
                        'vip_level' => $user->vip_level,
                        'product_id' => $product->id,
                        'task_name' => $request->task_name ?? 'VIP1',
                        'status' => 'pending',
                        'order_number' => $nextOrderNumber,
                        'initial_balance' => $user->balance,
                        'purchase_price' => $product->selling_price,
                        'commission_reward' => $product->commission_reward,
                    ]);

                    $user->balance -= $product->selling_price; // Deduct full selling_price for genuine lucky order
                    $user->save();

                    \Log::info('Genuine Lucky Order grabbed, pending status, selling_price deducted.', [
                        'user_id' => $user->id,
                        'product_id' => $product->id,
                        'new_balance' => $user->balance,
                    ]);
                }
            } else {
                // Regular Order and Forced Lucky Order Logic
                if ($user->balance < $product->commission_reward) {
                    \Log::warning('Insufficient balance for Order (Regular or Forced)', [
                        'user_id' => $user->id,
                        'balance' => $user->balance,
                        'commission_reward' => $product->commission_reward,
                        'product_type' => $product->type,
                        'is_forced' => $isForced,
                    ]);
                    return back()->with('error', 'Insufficient balance to grab this order.');
                }
                $user->balance -= $product->commission_reward; // Deduct only commission_reward
                $user->save();
                 \Log::info('Regular/Forced Order grabbed, commission_reward deducted.', [
                    'user_id' => $user->id,
                    'product_id' => $product->id,
                    'new_balance' => $user->balance,
                ]);
            }

            \Log::info('Balance deducted successfully', [
                'user_id' => $user->id,
                'new_balance' => $user->balance,
                'product_id' => $product->id,
            ]);

            return back()->with('success', 'Balance sufficient, commission deducted.');
        }, 5);
    }

    protected function handleLuckyOrderBalance(User $user, Product $product)
    {
        $newBalance = $user->balance - $product->selling_price; // Deduct only selling_price
        $user->balance = $newBalance;
        $user->save();

        \Log::info('Lucky Order balance updated', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'original_balance' => $user->balance,
            'selling_price' => $product->selling_price,
            'new_balance' => $newBalance,
        ]);
    }
}