<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\WithdrawController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('welcome');

// ================= ADMIN ROUTES =================
Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/users', [AdminController::class, 'users'])->name('admin.users');
    Route::patch('/users/{id}', [AdminController::class, 'updateUser'])->name('admin.users.update');
    Route::get('/deposit-clients', [AdminController::class, 'depositClients'])->name('admin.deposit-clients');
    Route::get('/qr-address-upload', [AdminController::class, 'showQrUploadForm'])->name('admin.qr-address-upload');
    Route::post('/qr-address-upload', [AdminController::class, 'uploadQrAndAddress'])->name('admin.qr-address-upload.store');
    Route::get('/update-wallet', [AdminController::class, 'updateWallet'])->name('admin.update-wallet');
    Route::post('/update-deposit/{depositId}', [AdminController::class, 'updateDepositStatus'])->name('admin.update-deposit');
    Route::post('/update-balance/{id}', [AdminController::class, 'updateBalance'])->name('admin.update-balance');

    // Fixed paths: remove duplicate '/admin' prefix (group already adds 'admin')
    Route::post('/freeze-balance/{id}', [AdminController::class, 'freezeBalance'])->name('admin.freeze-balance');
    Route::post('/unfreeze-balance/{id}', [AdminController::class, 'unfreezeBalance'])->name('admin.unfreeze-balance');

    Route::get('/withdraw', [AdminController::class, 'withdraw'])->name('admin.withdraw');
    // ================= PRODUCTS =================
    Route::get('/products', [AdminController::class, 'products'])->name('admin.products');
    Route::post('/products', [AdminController::class, 'storeProduct'])->name('admin.products.store');
    Route::post('/products/{id}', [AdminController::class, 'updateProduct'])->name('admin.products.update');
    Route::delete('/products/{id}', [AdminController::class, 'destroyProduct'])->name('admin.products.destroy');
    // ================= ORDER =================
    // Route::get('/orders', [AdminController::class, 'orders'])->name('admin.orders');
    // Route::post('/orders/send', [AdminController::class, 'sendOrder'])->name('admin.orders.send');
    // ================= TASK =================
    Route::get('/task-manager', [AdminController::class, 'taskManager'])->name('admin.task-manager');

    // ADD new route for viewing user tasks:
    Route::get('/tasks/{user}', [AdminController::class, 'userTasks'])->name('admin.tasks.details');
    Route::post('/tasks/{user}/replace/{task}', [AdminController::class, 'replaceWithLuckyOrder']);

    Route::post('/toggle-lucky-order-flag', [AdminController::class, 'toggleLuckyOrderFlag'])->name('admin.toggle-lucky-order-flag');
    Route::post('/tasks/{user}/reset', [AdminController::class, 'resetUserTasks']);
    Route::post('/tasks/{user}/delete', [AdminController::class, 'deleteUserTasks']);
});

// ================= DASHBOARD =================
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

// ================= AUTHENTICATED USER ROUTES =================
Route::middleware('auth')->group(function () {
    Route::get('/balance', function () {
        $user = auth()->user();
        return response()->json([
            'balance' => (float) $user->balance,
            'vip_level' => $user->vip_level,
            'avatar_url' => $user->avatar_url,
        ]);
    })->name('balance');

    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/change-password', function () {
        return inertia('Profile/ChangePassword');
    })->name('password.change');
    Route::put('/password', [PasswordController::class, 'update'])->name('password.update');

    Route::get('/withdraw', [WithdrawController::class, 'index'])->name('withdraw');
    Route::get('/withdraw/history', [WithdrawController::class, 'history'])->name('withdraw.history');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/deposit', [DepositController::class, 'index'])->name('deposit.index');
    Route::post('/deposit', [DepositController::class, 'store'])->name('deposit.store');
    Route::get('/deposit/history', [DepositController::class, 'history'])->name('deposit.history');
    Route::get('/vip/{level}/purchase', [DepositController::class, 'vipPurchase'])->middleware(['auth', 'verified'])->name('vip.purchase');

    Route::get('/my-orders', [DashboardController::class, 'orders'])->name('orders.index'); 
    Route::post('/orders/store', [DashboardController::class, 'storeOrder'])->name('orders.store');
    Route::post('/orders/check-balance', [DashboardController::class, 'checkBalance'])->name('orders.check-balance');
});

// ================= LOCALE CHANGE =================
Route::post('/locale/change', function (Request $request) {
    $locale = $request->input('locale');
    if (in_array($locale, ['en', 'es', 'it', 'ro', 'ru', 'de', 'bn', 'hi'])) {
        Session::put('locale', $locale);
        App::setLocale($locale);
    }
    return back();
})->name('locale.change');

require __DIR__ . '/auth.php';

Route::get('/admin/tasks/{user}', [AdminController::class, 'getUserTasks'])->middleware('auth');

// User withdraw routes (place inside auth middleware section)
Route::middleware(['auth'])->group(function () {
    Route::get('/withdraw', [\App\Http\Controllers\WithdrawController::class, 'index'])->name('withdraw');
    Route::post('/withdraw', [\App\Http\Controllers\WithdrawController::class, 'store'])->name('withdraw.store');
});

// Admin withdraw management (adjust admin middleware to your app)
Route::prefix('admin')->middleware(['auth','is_admin'])->name('admin.')->group(function () {
    Route::get('/withdrawals', [\App\Http\Controllers\Admin\WithdrawController::class, 'index'])->name('withdrawals');
    Route::post('/withdrawals/{id}/approve', [\App\Http\Controllers\Admin\WithdrawController::class, 'approve'])->name('withdrawals.approve');
    Route::post('/withdrawals/{id}/reject', [\App\Http\Controllers\Admin\WithdrawController::class, 'reject'])->name('withdrawals.reject');
    Route::get('/withdrawals/{id}/edit', [\App\Http\Controllers\Admin\WithdrawController::class, 'edit'])->name('withdrawals.edit');
    Route::post('/withdrawals/{id}/update', [\App\Http\Controllers\Admin\WithdrawController::class, 'update'])->name('withdrawals.update');
});