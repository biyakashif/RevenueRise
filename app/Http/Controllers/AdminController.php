<?php

namespace App\Http\Controllers;

use App\Events\BalanceUpdated;
use App\Models\Chat;
use App\Models\CryptoDepositDetail;
use App\Models\Deposit;
use App\Models\Product;
use App\Models\Task;
use App\Models\User;
use App\Models\UserOrder;
use App\Models\BalanceRecord;
use App\Models\Withdraw;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use App\Events\CryptoUpdated;

/**
 * Class AdminController
 *
 * Controller responsible for admin panel actions.
 * Note: Only formatting and docblocks were adjusted. No logic was modified.
 */
class AdminController extends Controller
{
    public function support()
    {
        return Inertia::render('Admin/Support');
    }

    public function chatWithUser($userId)
    {
        $user = User::findOrFail($userId);
        return Inertia::render('Admin/Chat', [
            'targetUser' => $user
        ]);
    }

    public function sendMessage(Request $request, $userId)
    {
        $request->validate([
            'message' => 'required_without:image|string',
            'image' => 'nullable|image|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('chat-images', 'public');
        }

        $chat = Chat::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $userId,
            'message' => $request->message ?? '',
            'image_path' => $imagePath,
        ]);

        broadcast(new \App\Events\NewChatMessage($chat))->toOthers();

        return response()->json(['success' => true]);
    }

    public function getMessages($userId)
    {
        $messages = Chat::where(function($query) use ($userId) {
            $query->where('sender_id', Auth::id())
                ->where('receiver_id', $userId);
        })
        ->orWhere(function($query) use ($userId) {
            $query->where('sender_id', $userId)
                ->where('receiver_id', Auth::id());
        })
        ->with(['sender:id,name,avatar_url', 'receiver:id,name,avatar_url'])
        ->latest()
        ->get();

        // Mark messages as read
        Chat::where('sender_id', $userId)
            ->where('receiver_id', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json($messages);
    }

    public function dashboard()
    {
        return Inertia::render('Admin/Dashboard');
    }

    public function users(Request $request)
    {
        $search = $request->input('search');
        $today = Carbon::today();

        $usersQuery = User::query()->where('role', 'user');

        if ($search) {
            $usersQuery->where(function ($query) use ($search) {
                $query->where('mobile_number', 'like', "%{$search}%")
                    ->orWhere('invitation_code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('vip_level', 'like', "%{$search}%");
            });
        }

        // Show most recently created users first
        $users = $usersQuery
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($user) use ($today) {
                $user->balance = (float) $user->balance;
                $user->frozen_balance = (float) $user->frozen_balance;
                $user->register_date = Carbon::parse($user->created_at)->toFormattedDateString();
                $user->is_new = Carbon::parse($user->created_at)->isSameDay($today);
                return $user;
            });

        $dailyNewUsers = User::where('role', 'user')
            ->whereDate('created_at', $today)
            ->count();

        return Inertia::render('Admin/Users', [
            'users' => $users,
            'search' => $search,
            'dailyNewUsers' => $dailyNewUsers,
        ]);
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::where('role', 'user')->findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'mobile_number' => 'required|string|max:255|unique:users,mobile_number,' . $user->id,
            'password' => 'nullable|string|min:6',
            'withdraw_password' => 'nullable|string|min:6',
            'invitation_code' => 'required|string|max:255|unique:users,invitation_code,' . $user->id,
            'balance' => 'required|numeric',
            'role' => 'required|string|in:user,admin',
            'referred_by' => 'nullable|string|exists:users,invitation_code',
            'vip_level' => 'required|string|max:255',
            'referral_percentage' => 'required|numeric|min:0|max:100',
        ]);

        if (empty($validatedData['password'])) {
            unset($validatedData['password']);
        }
        
        if (empty($validatedData['withdraw_password'])) {
            unset($validatedData['withdraw_password']);
        }

        $user->update($validatedData);

        event(new BalanceUpdated($user));

        return redirect()->back()->with('success', 'User updated successfully.');
    }

    public function destroyUser($id)
    {
        $user = User::where('role', 'user')->findOrFail($id);
        $user->delete();

        return redirect()->back()->with('success', 'User deleted successfully.');
    }

    public function bulkUpdateReferralPercentage(Request $request)
    {
        $request->validate([
            'percentage' => 'required|numeric|min:0|max:100',
        ]);

        User::where('role', 'user')->update(['referral_percentage' => $request->percentage]);

        return redirect()->back()->with('success', 'Task percentage updated for all users successfully.');
    }


    public function showQrUploadForm()
    {
        $cryptoList = $this->fetchCryptoList();
        $existingCryptos = CryptoDepositDetail::where('is_active', true)->get();

        return Inertia::render('Admin/QRAddressUpload', [
            'cryptoList' => $cryptoList,
            'existingCryptos' => $existingCryptos,
        ]);
    }

    public function uploadQrAndAddress(Request $request)
    {
        $multiNetworkCryptos = ['USDT', 'USDC'];
        $currency = $request->input('currency');
        
        if (in_array($currency, $multiNetworkCryptos)) {
            // Handle multi-network cryptos (USDT/USDC)
            $validated = $request->validate([
                'currency' => 'required|string|max:10',
                'trc20_qr_code' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'trc20_address' => 'required|string|max:255',
                'erc20_qr_code' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'erc20_address' => 'required|string|max:255',
                'bep20_qr_code' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'bep20_address' => 'required|string|max:255',
            ]);
            
            $networks = ['TRC20', 'ERC20', 'BEP20'];
            $symbol = strtolower($currency);
            
            foreach ($networks as $network) {
                $networkLower = strtolower($network);
                $data = [
                    'symbol' => $symbol,
                    'currency' => $currency,
                    'network' => $network,
                    'address' => $validated["{$networkLower}_address"],
                    'is_active' => true,
                ];
                
                if ($request->hasFile("{$networkLower}_qr_code")) {
                    $path = $request->file("{$networkLower}_qr_code")->store('qr_codes', 'public');
                    $data['qr_code'] = $path;
                }
                
                CryptoDepositDetail::create($data);
            }
        } else {
            // Handle single network cryptos
            $validated = $request->validate([
                'qr_code' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'address' => 'required|string|max:255',
                'currency' => 'required|string|max:10',
                'network' => 'required|string|max:50',
            ]);
            
            $symbol = strtolower($validated['currency']);
            $data = [
                'symbol' => $symbol,
                'address' => $validated['address'],
                'currency' => $validated['currency'],
                'network' => $validated['network'],
                'is_active' => true,
            ];
            
            if ($request->hasFile('qr_code')) {
                $path = $request->file('qr_code')->store('qr_codes', 'public');
                $data['qr_code'] = $path;
            }
            
            CryptoDepositDetail::create($data);
        }
        
        $cryptos = CryptoDepositDetail::where('is_active', true)->get();
        broadcast(new CryptoUpdated($cryptos, 'added'));
        
        return redirect()->back()->with('success', 'Cryptocurrency added successfully.');
    }

    public function updateQrAndAddress(Request $request, $id)
    {
        $crypto = CryptoDepositDetail::findOrFail($id);
        $multiNetworkCryptos = ['USDT', 'USDC'];
        $currency = $request->input('currency');
        
        if (in_array($currency, $multiNetworkCryptos)) {
            // Handle multi-network cryptos (USDT/USDC)
            $validated = $request->validate([
                'currency' => 'required|string|max:10',
                'trc20_qr_code' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'trc20_address' => 'nullable|string|max:255',
                'erc20_qr_code' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'erc20_address' => 'nullable|string|max:255',
                'bep20_qr_code' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'bep20_address' => 'nullable|string|max:255',
            ]);
            
            $networks = ['TRC20', 'ERC20', 'BEP20'];
            $symbol = strtolower($currency);
            
            foreach ($networks as $network) {
                $networkLower = strtolower($network);
                $existing = CryptoDepositDetail::where('currency', $currency)
                    ->where('network', $network)
                    ->first();
                
                if ($validated["{$networkLower}_address"]) {
                    $data = [
                        'symbol' => $symbol,
                        'currency' => $currency,
                        'network' => $network,
                        'address' => $validated["{$networkLower}_address"],
                        'is_active' => true,
                    ];
                    
                    if ($request->hasFile("{$networkLower}_qr_code")) {
                        if ($existing && $existing->qr_code) {
                            Storage::disk('public')->delete($existing->qr_code);
                        }
                        $path = $request->file("{$networkLower}_qr_code")->store('qr_codes', 'public');
                        $data['qr_code'] = $path;
                    }
                    
                    if ($existing) {
                        $existing->update($data);
                    } else {
                        CryptoDepositDetail::create($data);
                    }
                }
            }
        } else {
            // Handle single network cryptos
            $validated = $request->validate([
                'qr_code' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'address' => 'required|string|max:255',
                'currency' => 'required|string|max:10',
                'network' => 'required|string|max:50',
            ]);
            
            $symbol = strtolower($validated['currency']);
            $data = [
                'symbol' => $symbol,
                'address' => $validated['address'],
                'currency' => $validated['currency'],
                'network' => $validated['network'],
            ];
            
            if ($request->hasFile('qr_code')) {
                if ($crypto->qr_code) {
                    Storage::disk('public')->delete($crypto->qr_code);
                }
                $path = $request->file('qr_code')->store('qr_codes', 'public');
                $data['qr_code'] = $path;
            }
            
            $crypto->update($data);
        }
        
        $cryptos = CryptoDepositDetail::where('is_active', true)->get();
        broadcast(new CryptoUpdated($cryptos, 'updated'));
        
        return redirect()->back()->with('success', 'Cryptocurrency updated successfully.');
    }

    public function destroyQrAndAddress($id)
    {
        $crypto = CryptoDepositDetail::findOrFail($id);
        
        // Delete QR code file if exists
        if ($crypto->qr_code) {
            Storage::disk('public')->delete($crypto->qr_code);
        }
        
        $crypto->delete();

        // Broadcast update to all users
        $cryptos = CryptoDepositDetail::where('is_active', true)->get();
        broadcast(new CryptoUpdated($cryptos, 'deleted'));

        return redirect()->back()->with('success', 'Cryptocurrency deleted successfully.');
    }

    private function fetchCryptoList()
    {
        // Fetch top 15 cryptocurrencies from CoinGecko API
        try {
            $url = 'https://api.coingecko.com/api/v3/coins/markets?vs_currency=usd&order=market_cap_desc&per_page=15&page=1&sparkline=false';

            // Use cURL for better reliability
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($response && $httpCode === 200) {
                $data = json_decode($response, true);

                if (is_array($data) && count($data) > 0) {
                    $cryptoList = [];
                    foreach ($data as $crypto) {
                        $cryptoList[] = [
                            'id' => $crypto['id'] ?? '',
                            'symbol' => strtoupper($crypto['symbol'] ?? ''),
                            'name' => $crypto['name'] ?? '',
                            'image' => $crypto['image'] ?? '',
                        ];
                    }

                    // Ensure we have at least 15 items, fill with fallback if needed
                    if (count($cryptoList) < 15) {
                        $fallbackList = $this->getFallbackCryptoList();
                        foreach ($fallbackList as $fallback) {
                            if (!in_array($fallback['symbol'], array_column($cryptoList, 'symbol'))) {
                                $cryptoList[] = $fallback;
                                if (count($cryptoList) >= 15) break;
                            }
                        }
                    }

                    return array_slice($cryptoList, 0, 15);
                }
            }

            // If API fails, use fallback
            return $this->getFallbackCryptoList();

        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::warning('CoinGecko API failed: ' . $e->getMessage());
            return $this->getFallbackCryptoList();
        }
    }

    private function getFallbackCryptoList()
    {
        return [
            ['id' => 'bitcoin', 'symbol' => 'BTC', 'name' => 'Bitcoin', 'image' => 'https://assets.coingecko.com/coins/images/1/large/bitcoin.png'],
            ['id' => 'ethereum', 'symbol' => 'ETH', 'name' => 'Ethereum', 'image' => 'https://assets.coingecko.com/coins/images/279/large/ethereum.png'],
            ['id' => 'tether', 'symbol' => 'USDT', 'name' => 'Tether', 'image' => 'https://assets.coingecko.com/coins/images/325/large/Tether.png'],
            ['id' => 'binancecoin', 'symbol' => 'BNB', 'name' => 'Binance Coin', 'image' => 'https://assets.coingecko.com/coins/images/825/large/bnb-icon2_2x.png'],
            ['id' => 'solana', 'symbol' => 'SOL', 'name' => 'Solana', 'image' => 'https://assets.coingecko.com/coins/images/4128/large/solana.png'],
            ['id' => 'usd-coin', 'symbol' => 'USDC', 'name' => 'USD Coin', 'image' => 'https://assets.coingecko.com/coins/images/6319/large/USD_Coin_icon.png'],
            ['id' => 'cardano', 'symbol' => 'ADA', 'name' => 'Cardano', 'image' => 'https://assets.coingecko.com/coins/images/975/large/cardano.png'],
            ['id' => 'xrp', 'symbol' => 'XRP', 'name' => 'XRP', 'image' => 'https://assets.coingecko.com/coins/images/44/large/xrp-symbol-white-128.png'],
            ['id' => 'polkadot', 'symbol' => 'DOT', 'name' => 'Polkadot', 'image' => 'https://assets.coingecko.com/coins/images/12171/large/polkadot.png'],
            ['id' => 'dogecoin', 'symbol' => 'DOGE', 'name' => 'Dogecoin', 'image' => 'https://assets.coingecko.com/coins/images/5/large/dogecoin.png'],
            ['id' => 'avalanche-2', 'symbol' => 'AVAX', 'name' => 'Avalanche', 'image' => 'https://assets.coingecko.com/coins/images/12559/large/coin-round-red.png'],
            ['id' => 'shiba-inu', 'symbol' => 'SHIB', 'name' => 'Shiba Inu', 'image' => 'https://assets.coingecko.com/coins/images/11939/large/shiba.png'],
            ['id' => 'chainlink', 'symbol' => 'LINK', 'name' => 'Chainlink', 'image' => 'https://assets.coingecko.com/coins/images/877/large/chainlink-new-logo.png'],
            ['id' => 'matic-network', 'symbol' => 'MATIC', 'name' => 'Polygon', 'image' => 'https://assets.coingecko.com/coins/images/4713/large/matic-token-icon.png'],
            ['id' => 'litecoin', 'symbol' => 'LTC', 'name' => 'Litecoin', 'image' => 'https://assets.coingecko.com/coins/images/2/large/litecoin.png'],
        ];
    }

    public function fetchCryptoDetails(Request $request)
    {
        $cryptoId = $request->query('crypto_id');

        if (!$cryptoId) {
            return response()->json(['error' => 'Crypto ID is required'], 400);
        }

        try {
            $url = "https://api.coingecko.com/api/v3/coins/{$cryptoId}?localization=false&tickers=false&market_data=true&community_data=false&developer_data=false&sparkline=false";

            // Use cURL for better reliability
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($response && $httpCode === 200) {
                $data = json_decode($response, true);

                if ($data) {
                    $cryptoDetails = [
                        'id' => $data['id'] ?? '',
                        'symbol' => strtoupper($data['symbol'] ?? ''),
                        'name' => $data['name'] ?? '',
                        'image' => $data['image']['large'] ?? '',
                        'current_price' => $data['market_data']['current_price']['usd'] ?? 0,
                        'market_cap' => $data['market_data']['market_cap']['usd'] ?? 0,
                        'market_cap_rank' => $data['market_data']['market_cap_rank'] ?? 0,
                        'price_change_24h' => $data['market_data']['price_change_24h'] ?? 0,
                        'price_change_percentage_24h' => $data['market_data']['price_change_percentage_24h'] ?? 0,
                        'last_updated' => $data['last_updated'] ?? '',
                    ];

                    return response()->json($cryptoDetails);
                }
            }

            // If API fails, try to get from fallback list
            $fallbackList = $this->getFallbackCryptoList();
            $fallbackCrypto = collect($fallbackList)->firstWhere('id', $cryptoId);

            if ($fallbackCrypto) {
                return response()->json(array_merge($fallbackCrypto, [
                    'current_price' => 0,
                    'market_cap' => 0,
                    'market_cap_rank' => 0,
                    'price_change_24h' => 0,
                    'price_change_percentage_24h' => 0,
                    'last_updated' => now()->toISOString(),
                ]));
            }

            return response()->json(['error' => 'Cryptocurrency not found'], 404);

        } catch (\Exception $e) {
            \Log::warning('CoinGecko API failed for crypto details: ' . $e->getMessage());

            // Return fallback data if available
            $fallbackList = $this->getFallbackCryptoList();
            $fallbackCrypto = collect($fallbackList)->firstWhere('id', $cryptoId);

            if ($fallbackCrypto) {
                return response()->json(array_merge($fallbackCrypto, [
                    'current_price' => 0,
                    'market_cap' => 0,
                    'market_cap_rank' => 0,
                    'price_change_24h' => 0,
                    'price_change_percentage_24h' => 0,
                    'last_updated' => now()->toISOString(),
                ]));
            }

            return response()->json(['error' => 'Failed to fetch crypto details'], 500);
        }
    }

    public function depositClients(Request $request)
    {
        $search = $request->input('search');

        $query = User::where('role', 'user')
            ->leftJoin('deposits', 'users.id', '=', 'deposits.user_id')
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

        // Get pending deposits count per user
        $pendingDeposits = Deposit::where('status', 'pending')
            ->selectRaw('user_id, COUNT(*) as count')
            ->groupBy('user_id')
            ->pluck('count', 'user_id')
            ->toArray();

        if ($request->wantsJson()) {
            return response()->json([
                'data' => $users->items(),
                'pending_deposits' => $pendingDeposits
            ]);
        }

        return Inertia::render('Admin/DepositClients', [
            'initialUsers' => $users->items(),
            'initialPage' => $users->currentPage(),
            'initialLastPage' => $users->lastPage(),
            'search' => $search,
            'pendingDeposits' => $pendingDeposits,
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
                    $depositsQuery->where('user_id', $user->id);
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
            $user = User::find($deposit->user_id);

            if ($user && $user->role === 'user') {
                // Use converted amount if provided (from Coinbase API conversion), otherwise use original amount
                $amountToAdd = $request->input('converted_amount', $deposit->amount);

                // Only add balance for non-VIP deposits
                if (empty($deposit->vip_level)) {
                    $user->balance += $amountToAdd;

                    // Create balance record for deposit
                    BalanceRecord::create([
                        'user_id' => $user->id,
                        'type' => 'deposit',
                        'amount' => $amountToAdd,
                        'description' => $deposit->title ?: 'Deposit approved',
                    ]);
                }

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

        // Broadcast the deposit status update to the user and admin
        $deposit->load('user');
        broadcast(new \App\Events\DepositStatusUpdated($deposit));

        return redirect()->back()->with('success', 'Deposit status updated successfully.');
    }    public function products()
    {
        $products = Product::latest()->get();

        return Inertia::render('Admin/Products', ['products' => $products]);
    }

    public function storeProduct(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:VIP1,VIP2,VIP3,VIP4,VIP5,VIP6,VIP7,Lucky Order',
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
            'type' => 'required|in:VIP1,VIP2,VIP3,VIP4,VIP5,VIP6,VIP7,Lucky Order',
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

    public function bulkUpdateCommission(Request $request)
    {
        $request->validate([
            'product_type' => 'required|string|in:VIP1,VIP2,VIP3,VIP4,VIP5,VIP6,VIP7,Lucky Order',
            'commission_percentage' => 'required|numeric|min:0|max:100',
        ]);

        try {
            $updatedCount = Product::where('type', $request->product_type)
                ->update(['commission_percentage' => $request->commission_percentage]);

            return redirect()->route('admin.products')
                ->with('success', "Commission updated successfully for {$updatedCount} products of type {$request->product_type}!");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update commission: ' . $e->getMessage());
        }
    }

    public function taskManager()
    {
        $tasks = Task::with('product')
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->all();

        $vipsProducts = Product::whereIn('type', ['VIPs', 'VIP1'])->get();
        $luckyOrderProducts = Product::where('type', 'Lucky Order')->get();

        $users = User::where('role', 'user')->get(['id', 'name', 'mobile_number', 'vip_level', 'force_lucky_order', 'tasks_auto_reset']);

        $today = Carbon::today()->toDateString();

        // Correctly get confirmed stats for tasks assigned to users.
        $confirmedStats = DB::table('user_orders')
            ->join('tasks', function ($join) {
                $join->on('user_orders.product_id', '=', 'tasks.product_id')
                     ->on('user_orders.user_id', '=', 'tasks.user_id');
            })
            ->select(
                'user_orders.user_id',
                DB::raw('COUNT(DISTINCT user_orders.id) as confirmed_count'),
                DB::raw('MAX(user_orders.created_at) as last_confirmed_at')
            )
            ->where('user_orders.status', 'confirmed')
            ->whereDate('user_orders.created_at', $today)
            ->groupBy('user_orders.user_id')
            ->get()
            ->keyBy('user_id');

        $userTaskProgress = $users->map(function ($u) use ($confirmedStats) {
            $total = Task::where('user_id', $u->id)->count();
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
                'tasks_auto_reset' => $u->tasks_auto_reset,
            ];
        })->values();

        $assignedUserIds = Task::distinct('user_id')->pluck('user_id')->toArray();

        return Inertia::render('Admin/TaskManager', [
            'tasks' => $tasks,
            'vipsProducts' => $vipsProducts,
            'luckyOrderProducts' => $luckyOrderProducts,
            'userTaskProgress' => $userTaskProgress,
            'users' => $users,
            'assignedUserIds' => $assignedUserIds,
        ]);
    }

    public function userTasks(User $user)
    {
        $tasks = Task::where('user_id', $user->id)
            ->with('product')
            ->orderBy('position')
            ->get()
            ->map(function ($task) use ($user) {
                $order = UserOrder::where('user_id', $user->id)
                    ->where('product_id', $task->product_id)
                    ->first();
                return [
                    'id' => $task->id,
                    'name' => $task->product_type,
                    'product_id' => $task->product_id,
                    'product_type' => $task->product_type,
                    'position' => $task->position,
                    'status' => $order ? $order->status : 'pending',
                    'product' => $task->product ? [
                        'id' => $task->product->id,
                        'product_id' => $task->product->product_id,
                        'title' => $task->product->title,
                        'description' => $task->product->description,
                        'purchase_price' => $task->product->purchase_price,
                        'selling_price' => $task->product->selling_price,
                        'commission_reward' => $task->product->commission_reward,
                        'commission_percentage' => $task->product->commission_percentage,
                        'image_path' => $task->product->image_path,
                        'type' => $task->product->type,
                    ] : null,
                ];
            });

        return response()->json(['user' => $user, 'tasks' => $tasks]);
    }

    public function getLuckyOrderProducts()
    {
        $products = Product::where('type', 'Lucky Order')->get();
        return response()->json(['products' => $products]);
    }

    public function replaceWithLuckyOrder(Request $request, User $user, Task $task)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id'
        ]);

        $luckyProduct = Product::where('id', $request->product_id)
            ->where('type', 'Lucky Order')
            ->first();

        if ($luckyProduct) {
            $task->product_id = $luckyProduct->id;
            $task->product_type = 'Lucky Order';
            $task->save();
        }

        return response()->json(['success' => true, 'message' => 'Task replaced with Lucky Order.']);
    }

    public function resetUserTasks(User $user)
    {
        try {
            Log::info('Resetting tasks and user orders for user: ' . $user->id);
            DB::beginTransaction();

            // Reset order_reward when tasks are reset
            $user->order_reward = 0.00;
            $user->save();

            // Reset user orders
            UserOrder::where('user_id', $user->id)->delete();

            $assignedTasks = Task::where('user_id', $user->id)->orderBy('position')->get();

            if ($assignedTasks->isEmpty()) {
                Log::info('No tasks to reset for user: ' . $user->id);
                DB::rollBack();
                return;
            }

            // Get all current product IDs for this user to avoid assigning the same product
            $currentProductIds = $assignedTasks->pluck('product_id')->toArray();

            foreach ($assignedTasks as $task) {
                // Find a new product of the same type, excluding current ones if possible
                $newProduct = Product::where('type', $task->product_type)
                    ->whereNotIn('id', $currentProductIds)
                    ->inRandomOrder()
                    ->first();

                // If no new product is found (e.g., all have been used), fall back to any random product of the same type
                if (!$newProduct) {
                    $newProduct = Product::where('type', $task->product_type)
                        ->inRandomOrder()
                        ->first();
                }

                if ($newProduct) {
                    $task->product_id = $newProduct->id;
                    // The position remains the same
                    $task->save();

                    // Add the new product ID to the list of current ones for this transaction
                    // to minimize duplicates within the same reset operation.
                    $currentProductIds[] = $newProduct->id;
                }
            }

            DB::commit();
            Log::info('Tasks and user orders reset successfully for user: ' . $user->id);

            // Broadcast event so frontend updates live
            event(new \App\Events\UserProgressReset($user->id));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error resetting tasks and user orders for user: ' . $user->id . ' - ' . $e->getMessage());
        }
    }

    public function deleteUserTasks(User $user)
    {
        // Reset order_reward when tasks are deleted
        $user->order_reward = 0.00;
        $user->save();

        // Delete tasks and user orders
        Task::where('user_id', $user->id)->delete();
        UserOrder::where('user_id', $user->id)->delete();

    $user->update(['tasks_auto_reset' => false]);

    // Broadcast event so frontend updates live
    event(new \App\Events\UserProgressReset($user->id));

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

    public function assignTasks(Request $request)
    {
        try {
            $request->validate([
                'userId' => 'required|exists:users,id',
                'tasksNumber' => 'required|integer|min:1',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 400);
        }

        Log::info('Assign Tasks Request:', $request->all());

        $user = User::findOrFail($request->userId);
        $tasksNumber = $request->tasksNumber;

        $products = Product::where('type', $user->vip_level)->inRandomOrder()->get();

        if ($products->isEmpty()) {
            Log::error('No products found for user VIP level:', ['vip_level' => $user->vip_level]);
            return response()->json(['message' => "No products found for user VIP level: {$user->vip_level}."], 400);
        }

        $tasks = [];

        $regularProducts = Product::where('type', $user->vip_level)->inRandomOrder()->limit($tasksNumber)->get()->shuffle();

        for ($i = 1; $i <= $tasksNumber; $i++) {
            $product = null;

            if ($regularProducts->isNotEmpty()) {
                $product = $regularProducts->pop();
            }

            // Fallback if we run out of unique products
            if (!$product) {
                $product = Product::where('type', $user->vip_level)->inRandomOrder()->first();
            }

            if (!$product) {
                Log::error('Failed to fetch a product for task assignment.');
                continue; // Skip this task if no product can be found
            }

            $tasks[] = [
                'user_id' => $user->id,
                'product_id' => $product->id,
                'product_type' => $product->type,
                'position' => $i,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        try {
            DB::beginTransaction();

            // Reset order_reward when tasks are reassigned (do this first)
            $user->order_reward = 0.00;
            $user->save();

            // Delete existing tasks for the user
            Task::where('user_id', $user->id)->delete();

            // Insert new tasks
            Task::insert($tasks);

            // Re-enable auto-reset for the user
            $user->update(['tasks_auto_reset' => true]);

            DB::commit();

            Log::info('Tasks assigned successfully:', ['tasks' => $tasks]);

            // Broadcast event so user's Orders.vue updates live without refresh
            event(new \App\Events\UserProgressReset($user->id));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error assigning tasks:', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to assign tasks.'], 500);
        }

        return response()->json(['message' => 'Tasks assigned successfully.'], 200);
    }

    public function getAssignedUsers()
    {
        $assignedUserIds = Task::distinct('user_id')->pluck('user_id');
        return response()->json(['assignedUserIds' => $assignedUserIds]);
    }

    public function contactSettings()
    {
        $settings = \App\Models\ContactSetting::first() ?? new \App\Models\ContactSetting();
        return Inertia::render('Admin/ContactSettings', ['settings' => $settings]);
    }

    public function updateContactSettings(Request $request)
    {
        $request->validate([
            'show_email' => 'boolean',
            'email' => 'nullable|email',
            'show_whatsapp' => 'boolean',
            'whatsapp' => 'nullable|string',
            'show_telegram' => 'boolean',
            'telegram' => 'nullable|string',
            'show_office' => 'boolean',
            'office_address' => 'nullable|string',
        ]);

        $settings = \App\Models\ContactSetting::first();
        if (!$settings) {
            $settings = \App\Models\ContactSetting::create($request->all());
        } else {
            $settings->update($request->all());
        }

        return redirect()->back()->with('success', 'Contact settings updated successfully.');
    }

    public function autoReplySettings()
    {
        $settings = \App\Models\AutoReplySetting::first() ?? new \App\Models\AutoReplySetting();
        $messages = \App\Models\AutoReplyMessage::orderBy('order')->get();
        $contactSettings = \App\Models\ContactSetting::first();
        
        return Inertia::render('Admin/AutoReplySettings', [
            'settings' => $settings,
            'messages' => $messages,
            'contactSettings' => $contactSettings
        ]);
    }

    public function updateAutoReplySettings(Request $request)
    {
        $request->validate([
            'is_enabled' => 'boolean',
            'messages' => 'array',
            'messages.*.message' => 'required|string',
            'messages.*.include_contact_info' => 'boolean',
            'messages.*.image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
            'messages.*.video' => 'nullable|mimes:mp4,mkv|max:30720', // 30MB max
        ]);

        $settings = \App\Models\AutoReplySetting::first();
        if (!$settings) {
            $settings = \App\Models\AutoReplySetting::create([
                'is_enabled' => $request->is_enabled,
                'include_contact_info' => false // This is now per-message, so we can set it to false globally
            ]);
        } else {
            $settings->update([
                'is_enabled' => $request->is_enabled,
                'include_contact_info' => false // This is now per-message, so we can set it to false globally
            ]);
        }

        // Update messages
        \App\Models\AutoReplyMessage::truncate();
        foreach ($request->messages as $index => $messageData) {
            $imagePath = null;
            $videoPath = null;

            // Handle image upload
            if ($request->hasFile("messages.{$index}.image")) {
                $imageFile = $request->file("messages.{$index}.image");
                $imageName = time() . '_' . $index . '_auto_reply.' . $imageFile->getClientOriginalExtension();
                $imagePath = $imageFile->storeAs('auto_reply_images', $imageName, 'public');
            }

            // Handle video upload
            if ($request->hasFile("messages.{$index}.video")) {
                $videoFile = $request->file("messages.{$index}.video");
                $videoName = time() . '_' . $index . '_auto_reply.' . $videoFile->getClientOriginalExtension();
                $videoPath = $videoFile->storeAs('auto_reply_videos', $videoName, 'public');
            }

            \App\Models\AutoReplyMessage::create([
                'message' => $messageData['message'],
                'order' => $index + 1,
                'is_active' => true,
                'include_contact_info' => $messageData['include_contact_info'] ?? false,
                'image_path' => $imagePath,
                'video_path' => $videoPath
            ]);
        }

        // Return JSON response for AJAX requests
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Auto reply settings updated successfully.'
            ]);
        }

        return redirect()->back()->with('success', 'Auto reply settings updated successfully.');
    }

    public function blockUser(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        
        \App\Models\UserBlock::updateOrCreate(
            ['user_id' => $userId],
            ['blocked_at' => now()]
        );

        return redirect()->back()->with('success', 'User blocked successfully.');
    }

    public function unblockUser(Request $request, $userId)
    {
        \App\Models\UserBlock::where('user_id', $userId)->delete();
        return redirect()->back()->with('success', 'User unblocked successfully.');
    }

    public function blockedUsers()
    {
        $blockedUsers = \App\Models\UserBlock::with('user')->latest('blocked_at')->get();
        return Inertia::render('Admin/BlockedUsers', ['blockedUsers' => $blockedUsers]);
    }

    public function getPendingDepositsCount()
    {
        $count = Deposit::where('status', 'pending')->count();
        return response()->json(['count' => $count]);
    }

    public function getPendingWithdrawalsCount()
    {
        $count = Withdraw::where('status', 'under review')->count();
        return response()->json(['count' => $count]);
    }

    public function getUnassignedUsersCount()
    {
        // Get all user IDs that have tasks assigned
        $assignedUserIds = Task::distinct('user_id')->pluck('user_id');
        
        // Count users who don't have any tasks (excluding admins)
        $count = User::whereNotIn('id', $assignedUserIds)
                    ->where('role', '!=', 'admin')
                    ->count();
        
        return response()->json(['count' => $count]);
    }
}
