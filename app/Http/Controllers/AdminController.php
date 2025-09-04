<?php

namespace App\Http\Controllers;

use App\Events\BalanceUpdated;
use App\Models\CryptoDepositDetail;
use App\Models\Deposit;
use App\Models\Product;
use App\Models\Task;
use App\Models\User;
use App\Models\UserOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

/**
 * Class AdminController
 *
 * Controller responsible for admin panel actions.
 * Note: Only formatting and docblocks were adjusted. No logic was modified.
 */
class AdminController extends Controller
{
    public function dashboard()
    {
        return Inertia::render('Admin/Dashboard');
    }

    public function users(Request $request)
    {
        $search = $request->input('search');

        $usersQuery = User::query()->where('role', 'user');

        if ($search) {
            $usersQuery->where(function ($query) use ($search) {
                $query->where('mobile_number', 'like', "%{$search}%")
                    ->orWhere('invitation_code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        // Show most recently updated users (activity) first
        $users = $usersQuery
            ->orderBy('updated_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get([
                'id',
                'name',
                'mobile_number',
                'invitation_code',
                'balance',
                'frozen_balance',
                'created_at',
                'updated_at',
            ])
            ->map(function ($user) {
                $user->balance = (float) $user->balance;
                $user->frozen_balance = (float) $user->frozen_balance;

                return $user;
            });

        return Inertia::render('Admin/Users', [
            'users' => $users,
            'search' => $search,
        ]);
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::where('role', 'user')->findOrFail($id);

        $data = $request->only(['name', 'mobile_number', 'invitation_code', 'balance']);

        if (isset($data['balance'])) {
            $data['balance'] = (float) $data['balance'];
        }

        $user->update($data);

        // Ensure updated_at is touched
        $user->touch();

        event(new BalanceUpdated($user));

        return response()->json([
            'success' => 'User updated successfully.',
            'user' => array_merge($user->toArray(), ['balance' => (float) $user->balance]),
        ]);
    }

    public function withdraw()
    {
        return Inertia::render('Admin/Withdraw');
    }

    // ---------------------------------------------------------------------
    // Orders (commented out)
    // ---------------------------------------------------------------------
    // ...existing commented methods kept as-is...

    public function showQrUploadForm()
    {
        $detail = CryptoDepositDetail::where('symbol', 'usdt')->first();

        return Inertia::render('Admin/QRAddressUpload', [
            'initialAddress' => $detail ? $detail->address : '',
            'initialQrCode' => $detail ? $detail->qr_code : '',
        ]);
    }

    public function uploadQrAndAddress(Request $request)
    {
        $validated = $request->validate([
            'qr_code' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'address' => 'required|string|max:255',
        ]);

        $symbol = 'usdt';
        $data = ['address' => $validated['address']];

        if ($request->hasFile('qr_code')) {
            $file = $request->file('qr_code');
            $path = $file->store('qr_codes', 'public');
            $data['qr_code'] = $path;
        }

        CryptoDepositDetail::updateOrCreate(['symbol' => $symbol], $data);

        return redirect()->back()->with('success', 'QR code and wallet address updated successfully.');
    }

    public function depositClients(Request $request)
    {
        $search = $request->input('search');

        $query = User::where('role', 'user')
            ->leftJoin('deposits', 'users.mobile_number', '=', 'deposits.user_id')
            ->select('users.id', 'users.name', 'users.mobile_number', 'users.balance', 'users.frozen_balance')
            ->groupBy('users.id', 'users.name', 'users.mobile_number', 'users.balance', 'users.frozen_balance');

        if ($search) {
            $query->where('users.mobile_number', 'like', "%{$search}%");
        }

        $users = $query
            ->orderByRaw('COALESCE(MAX(deposits.updated_at), "1970-01-01") DESC')
            ->orderBy('users.created_at', 'desc')
            ->paginate(10)
            ->through(function ($user) {
                $user->balance = (float) ($user->balance ?? 0.00);
                $user->frozen_balance = (float) ($user->frozen_balance ?? 0.00);

                return $user;
            });

        if ($request->wantsJson()) {
            return response()->json($users);
        }

        return Inertia::render('Admin/DepositClients', [
            'initialUsers' => $users->items(),
            'initialPage' => $users->currentPage(),
            'initialLastPage' => $users->lastPage(),
            'search' => $search,
        ]);
    }

    public function updateWallet(Request $request)
    {
        $userId = $request->query('user_id');

        $user = null;
        $depositsQuery = Deposit::with(['user' => function ($query) {
            $query->select('id', 'name', 'mobile_number', 'balance', 'frozen_balance');
        }])->orderBy('created_at', 'desc');

        if ($userId) {
            $user = User::select('id', 'name', 'mobile_number', 'balance', 'frozen_balance')->find($userId);

            if ($user) {
                $user->balance = (float) ($user->balance ?? 0.00);
                $user->frozen_balance = (float) ($user->frozen_balance ?? 0.00);
                $depositsQuery->where('user_id', $user->mobile_number);
            }
        }

        $deposits = $depositsQuery->get();

        $balance = $user ? (float) ($user->balance ?? 0.00) : 0;
        $frozen_balance = $user ? (float) ($user->frozen_balance ?? 0.00) : 0;
        $userName = $user ? $user->name : null;
        $mobileNumber = $user ? $user->mobile_number : null;

        if ($request->wantsJson()) {
            return response()->json([
                'deposits' => $deposits,
                'selectedUserId' => $userId,
                'balance' => $balance,
                'frozen_balance' => $frozen_balance,
                'userName' => $userName,
                'mobile_number' => $mobileNumber,
            ]);
        }

        return Inertia::render('Admin/UpdateWallet', [
            'deposits' => $deposits,
            'selectedUserId' => $userId,
            'balance' => $balance,
            'frozen_balance' => $frozen_balance,
            'userName' => $userName,
            'mobile_number' => $mobileNumber,
        ]);
    }

    public function updateBalance(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'action' => 'required|in:add,subtract',
        ]);

        $user = User::findOrFail($id);
        $amount = (float) $request->amount;

        if ($request->action === 'subtract' && $user->balance < $amount) {
            return redirect()->back()->with('error', 'Insufficient balance to subtract.');
        }

        if ($request->action === 'add') {
            $user->balance += $amount;
        } elseif ($request->action === 'subtract') {
            $user->balance -= $amount;
        }

        $user->save();

        event(new BalanceUpdated($user));

        return redirect()->back()->with('success', 'Balance updated successfully.');
    }

    public function freezeBalance(Request $request, $id)
    {
        $request->validate(['amount' => 'required|numeric|min:0.01']);

        $user = User::findOrFail($id);
        $amount = (float) $request->amount;

        if ($user->balance < $amount) {
            return redirect()->back()->with('error', 'Insufficient balance to freeze.');
        }

        $user->balance -= $amount;
        $user->frozen_balance += $amount;
        $user->save();

        event(new BalanceUpdated($user));

        return redirect()->back()->with('success', 'Balance frozen successfully.');
    }

    public function unfreezeBalance(Request $request, $id)
    {
        $request->validate(['amount' => 'required|numeric|min:0.01']);

        $user = User::findOrFail($id);
        $amount = (float) $request->amount;

        if ($user->frozen_balance < $amount) {
            return redirect()->back()->with('error', 'Insufficient frozen balance to unfreeze.');
        }

        $user->frozen_balance -= $amount;
        $user->balance += $amount;
        $user->save();

        event(new BalanceUpdated($user));

        return redirect()->back()->with('success', 'Balance unfrozen successfully.');
    }

    public function updateDepositStatus(Request $request, $depositId)
    {
        $deposit = Deposit::findOrFail($depositId);
        $action = $request->input('action');

        if ($deposit->status === 'approved' && $action === 'approve') {
            return redirect()->back()->with('success', 'Deposit already approved.');
        }

        if ($action === 'approve') {
            $user = User::where('mobile_number', $deposit->user_id)->first();

            if ($user && $user->role === 'user') {
                $user->balance += $deposit->amount;

                if (!empty($deposit->vip_level)) {
                    $user->vip_level = $deposit->vip_level;
                }

                $user->save();
                event(new BalanceUpdated($user));
            }

            $deposit->status = 'approved';
        } elseif ($action === 'reject') {
            $deposit->status = 'rejected';
        }

        $deposit->save();

        return redirect()->back()->with('success', 'Deposit status updated successfully.');
    }

    public function products()
    {
        $products = Product::latest()->get();

        return Inertia::render('Admin/Products', ['products' => $products]);
    }

    public function storeProduct(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:VIP1,VIPs,Lucky Order',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'commission_percentage' => 'required|numeric|min:0|max:100',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            $image = $request->file('image');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $imagePath = $image->storeAs('products', $filename, 'public');

            $baseId = 1321456458;
            $randomPart = mt_rand(100000, 999999);
            $productId = $baseId + $randomPart * mt_rand(1, 100);

            $commission_reward = ($request->commission_percentage / 100) * $request->selling_price;

            DB::beginTransaction();

            $product = Product::create([
                'product_id' => $productId,
                'title' => $request->title,
                'description' => $request->description,
                'type' => $request->type,
                'purchase_price' => $request->purchase_price,
                'selling_price' => $request->selling_price,
                'commission_reward' => $commission_reward,
                'commission_percentage' => $request->commission_percentage,
                'image_path' => $imagePath,
            ]);

            DB::commit();

            return redirect()->route('admin.products')->with('success', 'Product uploaded successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Failed to upload product: ' . $e->getMessage());
        }
    }

    public function updateProduct(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:VIP1,VIPs,Lucky Order',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'commission_percentage' => 'required|numeric|min:0|max:100',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $filename = time() . '.' . $image->getClientOriginalExtension();
                $imagePath = $image->storeAs('products', $filename, 'public');

                if ($product->image_path) {
                    Storage::disk('public')->delete($product->image_path);
                }

                $product->image_path = $imagePath;
            }

            $commission_reward = ($request->commission_percentage / 100) * $request->selling_price;

            $product->title = $request->title;
            $product->description = $request->description;
            $product->type = $request->type;
            $product->purchase_price = $request->purchase_price;
            $product->selling_price = $request->selling_price;
            $product->commission_reward = $commission_reward;
            $product->commission_percentage = $request->commission_percentage;
            $product->save();

            return redirect()->route('admin.products')->with('success', 'Product updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update product: ' . $e->getMessage());
        }
    }

    public function destroyProduct($id)
    {
        $product = Product::findOrFail($id);

        try {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }

            $product->delete();

            return redirect()->route('admin.products')->with('success', 'Product deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete product: ' . $e->getMessage());
        }
    }

    public function taskManager()
    {
        $tasks = Task::with('product')
            ->orderBy('name')
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->all();

        $vipsProducts = Product::whereIn('type', ['VIPs', 'VIP1'])->get();
        $luckyOrderProducts = Product::where('type', 'Lucky Order')->get();

        $users = User::where('role', 'user')->get(['id', 'name', 'mobile_number', 'vip_level', 'force_lucky_order']);

        $taskTotalsBySet = Task::select('name', DB::raw('COUNT(*) as total'))
            ->groupBy('name')
            ->pluck('total', 'name');

        $today = Carbon::today()->toDateString();

        $confirmedStats = DB::table('user_orders')
            ->join('users', 'users.id', '=', 'user_orders.user_id')
            ->select(
                'user_orders.user_id',
                DB::raw('COUNT(*) as confirmed_count'),
                DB::raw('MAX(user_orders.created_at) as last_confirmed_at')
            )
            ->where('user_orders.status', 'confirmed')
            ->whereDate('user_orders.created_at', $today)
            ->whereColumn('user_orders.task_name', 'users.vip_level')
            ->groupBy('user_orders.user_id')
            ->get()
            ->keyBy('user_id');

        $userTaskProgress = $users->map(function ($u) use ($taskTotalsBySet, $confirmedStats) {
            $total = (int) ($taskTotalsBySet[$u->vip_level] ?? 0);
            $confirmed = (int) ($confirmedStats[$u->id]->confirmed_count ?? 0);
            $pending = max(0, $total - $confirmed);
            $last = $confirmedStats[$u->id]->last_confirmed_at ?? null;

            return [
                'user_id' => $u->id,
                'name' => $u->name,
                'mobile_number' => $u->mobile_number,
                'vip_level' => $u->vip_level,
                'task_set_total' => $total,
                'confirmed_count' => $confirmed,
                'pending_count' => $pending,
                'last_confirmed_at' => $last,
                'force_lucky_order' => $u->force_lucky_order,
            ];
        })->values();

        return Inertia::render('Admin/TaskManager', [
            'tasks' => $tasks,
            'vipsProducts' => $vipsProducts,
            'luckyOrderProducts' => $luckyOrderProducts,
            'userTaskProgress' => $userTaskProgress,
            'users' => $users,
        ]);
    }

    public function userTasks(User $user)
    {
        $tasks = Task::where('user_id', $user->id)
            ->with('product')
            ->orderBy('position')
            ->get();

        return response()->json(['user' => $user, 'tasks' => $tasks]);
    }

    public function replaceWithLuckyOrder(User $user, Task $task)
    {
        $luckyProduct = Product::where('type', 'Lucky Order')->inRandomOrder()->first();

        if ($luckyProduct) {
            $task->product_id = $luckyProduct->id;
            $task->product_type = 'Lucky Order';
            $task->save();
        }

        $tasks = Task::where('user_id', $user->id)
            ->with('product')
            ->orderBy('position')
            ->get();

        return response()->json(['tasks' => $tasks]);
    }

    public function resetUserTasks(User $user)
    {
        $user->assignTasks();

        // Also reset user_orders for this user
        UserOrder::where('user_id', $user->id)->delete();

        return response()->json(['success' => true]);
    }

    public function deleteUserTasks(User $user)
    {
        Task::where('user_id', $user->id)->delete();

        return response()->json(['success' => true]);
    }

    public function getUserTasks($userId)
    {
        $user = User::findOrFail($userId);

        // Get all tasks for this user
        $tasks = Task::with('product')->where('user_id', $user->id)->get();

        // Get confirmed product_ids from user_orders
        $confirmedProductIds = UserOrder::where('user_id', $user->id)
            ->where('status', 'confirmed')
            ->pluck('product_id')
            ->toArray();

        // Attach status to each task
        $tasks = $tasks->map(function ($task) use ($confirmedProductIds) {
            $taskArr = $task->toArray();
            $taskArr['status'] = in_array($task->product_id, $confirmedProductIds) ? 'confirmed' : 'pending';

            return $taskArr;
        });

        return response()->json(['user' => $user, 'tasks' => $tasks]);
    }
}
