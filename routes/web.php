
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
use Illuminate\Support\Facades\Broadcast;

// Broadcasting Routes (must be at the top)
Broadcast::routes(['middleware' => ['web']]);

// Welcome Route
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('welcome');

// Dashboard Route
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// ================= ADMIN ROUTES =================
Route::prefix('admin')->middleware(['auth', 'admin'])->name('admin.')->group(function () {
    // Admin Dashboard & User Management
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::patch('/users/{id}', [AdminController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{id}', [AdminController::class, 'destroyUser'])->name('users.destroy');
    
    // Admin Chat Routes
    Route::get('/support', [\App\Http\Controllers\Admin\ChatController::class, 'index'])->name('support');
    Route::get('/chat/users', [\App\Http\Controllers\Admin\ChatController::class, 'getUsers'])->name('chat.users');
    Route::get('/chat/{userId}/messages', [\App\Http\Controllers\Admin\ChatController::class, 'getMessages'])->name('chat.messages');
    Route::post('/chat/{userId}/send', [\App\Http\Controllers\Admin\ChatController::class, 'sendMessage'])->name('chat.send');
    Route::post('/chat/{userId}/broadcast', [\App\Http\Controllers\Admin\ChatController::class, 'broadcastMessage'])->name('chat.broadcast');
    Route::delete('/chat/{userId}/delete-history', [\App\Http\Controllers\Admin\ChatController::class, 'deleteChatHistory'])->name('chat.delete-history');
    
    // Admin Guest Chat Routes
    Route::get('/guest-chat/users', [\App\Http\Controllers\Admin\GuestChatController::class, 'getUsers'])->name('guest-chat.users');
    Route::get('/guest-chat/blocked', [\App\Http\Controllers\Admin\GuestChatController::class, 'getBlockedGuests'])->name('guest-chat.blocked');
    Route::get('/guest-chat/{sessionId}/messages', [\App\Http\Controllers\Admin\GuestChatController::class, 'getMessages'])->name('guest-chat.messages');
    Route::post('/guest-chat/{sessionId}/send', [\App\Http\Controllers\Admin\GuestChatController::class, 'sendMessage'])->name('guest-chat.send');
    Route::post('/guest-chat/{sessionId}/broadcast', [\App\Http\Controllers\Admin\GuestChatController::class, 'broadcastMessage'])->name('guest-chat.broadcast');
    Route::delete('/guest-chat/{sessionId}/delete-history', [\App\Http\Controllers\Admin\GuestChatController::class, 'deleteChatHistory'])->name('guest-chat.delete-history');
    Route::post('/guest-chat/{sessionId}/block', [\App\Http\Controllers\Admin\GuestChatController::class, 'blockUser'])->name('guest-chat.block');
    Route::post('/guest-chat/{sessionId}/unblock', [\App\Http\Controllers\Admin\GuestChatController::class, 'unblockUser'])->name('guest-chat.unblock');
    
    // Deposit Management
    Route::get('/deposit-clients', [AdminController::class, 'depositClients'])->name('deposit-clients');
    Route::get('/qr-address-upload', [AdminController::class, 'showQrUploadForm'])->name('qr-address-upload');
    Route::post('/qr-address-upload', [AdminController::class, 'uploadQrAndAddress'])->name('qr-address-upload.store');
    Route::put('/qr-address-upload/{id}', [AdminController::class, 'updateQrAndAddress'])->name('qr-address-upload.update');
    Route::delete('/qr-address-upload/{id}', [AdminController::class, 'destroyQrAndAddress'])->name('qr-address-upload.destroy');
    Route::get('/update-wallet', [AdminController::class, 'updateWallet'])->name('update-wallet');
    Route::post('/update-deposit/{depositId}', [AdminController::class, 'updateDepositStatus'])->name('update-deposit');
    
    // Balance Management
    Route::post('/update-balance/{id}', [AdminController::class, 'updateBalance'])->name('update-balance');
    Route::post('/freeze-balance/{id}', [AdminController::class, 'freezeBalance'])->name('freeze-balance');
    Route::post('/unfreeze-balance/{id}', [AdminController::class, 'unfreezeBalance'])->name('unfreeze-balance');
    
    // Product Management
    Route::get('/products', [AdminController::class, 'products'])->name('products');
    Route::post('/products', [AdminController::class, 'storeProduct'])->name('products.store');
    Route::post('/products/{id}', [AdminController::class, 'updateProduct'])->name('products.update');
    Route::delete('/products/{id}', [AdminController::class, 'destroyProduct'])->name('products.destroy');
    
    // Task Management
    Route::get('/task-manager', [AdminController::class, 'taskManager'])->name('task-manager');
    Route::get('/tasks/{user}', [AdminController::class, 'userTasks'])->name('tasks.details');
    Route::post('/tasks/{user}/replace/{task}', [AdminController::class, 'replaceWithLuckyOrder']);
    Route::post('/toggle-lucky-order-flag', [AdminController::class, 'toggleLuckyOrderFlag'])->name('toggle-lucky-order-flag');
    Route::post('/tasks/{user}/reset', [AdminController::class, 'resetUserTasks']);
    Route::post('/tasks/{user}/delete', [AdminController::class, 'deleteUserTasks']);
    Route::post('/tasks/assign', [AdminController::class, 'assignTasks'])->name('tasks.assign');
    Route::get('/tasks/assigned-users', [AdminController::class, 'getAssignedUsers'])->name('tasks.assigned-users');

    // Withdrawal Management
    Route::get('/withdrawals', [\App\Http\Controllers\Admin\WithdrawController::class, 'index'])->name('withdrawals');
    Route::post('/withdrawals/{id}/approve', [\App\Http\Controllers\Admin\WithdrawController::class, 'approve'])->name('withdrawals.approve');
    Route::post('/withdrawals/{id}/reject', [\App\Http\Controllers\Admin\WithdrawController::class, 'reject'])->name('withdrawals.reject');
    Route::get('/withdrawals/{id}/edit', [\App\Http\Controllers\Admin\WithdrawController::class, 'edit'])->name('withdrawals.edit');
    Route::post('/withdrawals/{id}/update', [\App\Http\Controllers\Admin\WithdrawController::class, 'update'])->name('withdrawals.update');
    Route::post('/users/{user}/withdraw-limit', [\App\Http\Controllers\Admin\WithdrawLimitController::class, 'update'])->name('users.withdraw-limit.update');
    
    // Slider Management
    Route::get('/sliders', [\App\Http\Controllers\Admin\SliderController::class, 'index'])->name('sliders');
    Route::post('/sliders', [\App\Http\Controllers\Admin\SliderController::class, 'store'])->name('sliders.store');
    Route::post('/sliders/{sliderImage}', [\App\Http\Controllers\Admin\SliderController::class, 'update'])->name('sliders.update');
    Route::delete('/sliders/{sliderImage}', [\App\Http\Controllers\Admin\SliderController::class, 'destroy'])->name('sliders.destroy');
    Route::post('/sliders/{sliderImage}/toggle', [\App\Http\Controllers\Admin\SliderController::class, 'toggle'])->name('sliders.toggle');
    
    // Contact Settings
    Route::get('/contact-settings', [AdminController::class, 'contactSettings'])->name('contact-settings');
    Route::post('/contact-settings', [AdminController::class, 'updateContactSettings'])->name('contact-settings.update');
    
    // Auto Reply Settings
    Route::get('/auto-reply', [AdminController::class, 'autoReplySettings'])->name('auto-reply');
    Route::post('/auto-reply', [AdminController::class, 'updateAutoReplySettings'])->name('auto-reply.update');
    
    // User Blocking
    Route::post('/users/{user}/block', [AdminController::class, 'blockUser'])->name('users.block');
    Route::post('/users/{user}/unblock', [AdminController::class, 'unblockUser'])->name('users.unblock');
    Route::get('/blocked-users', [AdminController::class, 'blockedUsers'])->name('blocked-users');
});

// ================= AUTHENTICATED USER ROUTES =================
Route::middleware('auth')->group(function () {
    // Chat Routes
    Route::get('/chat', [\App\Http\Controllers\ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/messages', [\App\Http\Controllers\ChatController::class, 'getMessages'])->name('chat.messages');
    Route::get('/chat/unread-count', [\App\Http\Controllers\ChatController::class, 'unreadCount'])->name('chat.unread-count');
    Route::post('/chat/send', [\App\Http\Controllers\ChatController::class, 'sendMessage'])->name('chat.send');
    Route::post('/chat/broadcast', [\App\Http\Controllers\ChatController::class, 'broadcastMessage'])->name('chat.broadcast');
    Route::post('/chat/upload-image', [\App\Http\Controllers\ChatController::class, 'uploadImage'])->name('chat.upload-image');
    Route::get('/chat/block-status', [\App\Http\Controllers\ChatController::class, 'checkBlockStatus'])->name('chat.block-status');

    // Balance Route
    Route::get('/balance', function () {
        $user = auth()->user();
        return response()->json([
            'balance' => (float) $user->balance,
            'vip_level' => $user->vip_level,
            'avatar_url' => $user->avatar_url,
        ]);
    })->name('balance');

    // Profile Routes
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar'])->name('profile.avatar');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Password Routes
    Route::get('/change-password', function () {
        return inertia('Profile/ChangePassword');
    })->name('password.change');
    Route::put('/password', [PasswordController::class, 'update'])->name('password.update');

    // Withdraw Routes
    Route::get('/withdraw', [WithdrawController::class, 'index'])->name('withdraw');
    Route::post('/withdraw', [WithdrawController::class, 'store'])->name('withdraw.store');
    Route::get('/withdraw/history', [WithdrawController::class, 'history'])->name('withdraw.history');


    // Balance Record Routes
    Route::get('/balance/records', [\App\Http\Controllers\BalanceRecordController::class, 'index'])->name('balance.records');

    // Auth Routes
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Deposit Routes
    Route::get('/deposit', [DepositController::class, 'index'])->name('deposit.index');
    Route::post('/deposit', [DepositController::class, 'store'])->name('deposit.store');
    Route::get('/deposit/history', [DepositController::class, 'history'])->name('deposit.history');
    Route::get('/vip/{level}/purchase', [DepositController::class, 'vipPurchase'])->name('vip.purchase');
    
    // Crypto API Routes (moved from admin routes for user access)
    Route::get('/crypto-details', [AdminController::class, 'fetchCryptoDetails'])->name('crypto-details');

    // Order Routes
    Route::get('/my-orders', [DashboardController::class, 'orders'])->name('orders.index'); 
    Route::post('/orders/store', [DashboardController::class, 'storeOrder'])->name('orders.store');
    Route::post('/orders/check-balance', [DashboardController::class, 'checkBalance'])->name('orders.check-balance');

    // Static Pages
    Route::get('/service', function () {
        return Inertia::render('Service');
    })->name('service');

    Route::get('/event', function () {
        return Inertia::render('Event');
    })->name('event');

// Contact Page Route
Route::get('/contact', function () {
    $contactSettings = \App\Models\ContactSetting::first();
    return Inertia::render('Contact', [
        'contactSettings' => $contactSettings
    ]);
})->name('contact');

    Route::get('/certificate', function () {
        return Inertia::render('Certificate');
    })->name('certificate');

    Route::get('/faqs', function () {
        return Inertia::render('Faqs');
    })->name('faqs');

    Route::get('/about', function () {
        return Inertia::render('About');
    })->name('about');
});

// ================= LOCALE CHANGE =================
Route::post('/locale/change', function (Request $request) {
    $locale = $request->input('locale');
    $validLocales = ['en', 'es', 'it', 'ro', 'ru', 'de', 'bn', 'hi'];
    
    if (!in_array($locale, $validLocales)) {
        return back()->with('error', 'Invalid locale');
    }
    
    Session::put('locale', $locale);
    Session::save();
    App::setLocale($locale);
    
    return back()->with('success', 'Language changed successfully')
        ->cookie('locale', $locale, 60 * 24 * 365); // 1 year cookie
})->name('locale.change')->middleware('web');

// ================= CAPTCHA ROUTES =================
Route::get('/captcha', [\App\Http\Controllers\CaptchaController::class, 'generate'])->name('captcha.generate');
Route::post('/captcha/verify', [\App\Http\Controllers\CaptchaController::class, 'verify'])->name('captcha.verify');

// ================= GUEST CHAT ROUTES =================
Route::post('/guest-chat/start', [\App\Http\Controllers\GuestChatController::class, 'startChat'])->name('guest-chat.start');
Route::get('/guest-chat/{sessionId}/messages', [\App\Http\Controllers\GuestChatController::class, 'getMessages'])->name('guest-chat.messages');
Route::post('/guest-chat/{sessionId}/broadcast', [\App\Http\Controllers\GuestChatController::class, 'broadcastMessage'])->name('guest-chat.broadcast');
Route::post('/guest-chat/{sessionId}/send', [\App\Http\Controllers\GuestChatController::class, 'sendFile'])->name('guest-chat.send');
Route::get('/guest-chat/{sessionId}/block-status', [\App\Http\Controllers\GuestChatController::class, 'checkBlockStatus'])->name('guest-chat.block-status');

// CSRF token refresh
Route::get('/csrf-token', function () {
    return response()->json(['token' => csrf_token()]);
})->middleware('web')->name('csrf-token');

require __DIR__ . '/auth.php';
