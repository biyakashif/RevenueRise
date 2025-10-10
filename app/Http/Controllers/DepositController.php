<?php

namespace App\Http\Controllers;

use App\Events\DepositCreated;
use App\Models\BalanceRecord;
use App\Models\CryptoDepositDetail;
use App\Models\Deposit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;


class DepositController extends Controller
{
   public function index(Request $request)
    {
        $cryptos = CryptoDepositDetail::where('is_active', true)->get();
        
        // Get crypto details with caching to avoid multiple API calls
        $cryptoDetails = [];
        foreach ($cryptos as $crypto) {
            $cryptoDetails[$crypto->currency] = Cache::remember(
                "crypto_details_{$crypto->currency}",
                now()->addMinutes(30), // Cache for 30 minutes
                function () use ($crypto) {
                    return $this->getCryptoDetails($crypto->currency);
                }
            );
        }
        
        // pass vip and prefill amount when coming from VIP purchase page
        $vip = $request->query('vip');
        $prefillAmount = $request->query('amount');

        return Inertia::render('Deposit', [
            'cryptos' => $cryptos,
            'cryptoDetails' => $cryptoDetails,
            'vip' => $vip,
            'prefillAmount' => $prefillAmount,
        ]);
    }

    public function vipPurchase(Request $request, $level = null)
    {
        // price mapping should match frontend
        $prices = [
            'VIP1' => null,
            'VIP2' => 300.0,
            'VIP3' => 750.0,
            'VIP4' => 1500.0,
            'VIP5' => 3500.0,
            'VIP6' => 6500.0,
            'VIP7' => 10000.0,
        ];

        $level = strtoupper($level);
        $price = $prices[$level] ?? null;

        return Inertia::render('VIP/Purchase', [
            'level' => $level,
            'levelPrice' => $price,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.00000001',
            'slip' => 'required|image|max:2048',
            'vip' => 'nullable|string',
            'crypto_id' => 'required|exists:crypto_deposit_details,id',
        ]);

        $path = $request->file('slip')->store('deposits', 'public');
        $user = auth()->user();
        $crypto = CryptoDepositDetail::findOrFail($request->crypto_id);

        $data = [
            'user_id' => $user->id,
            'symbol' => strtolower($crypto->currency),
            'amount' => $request->amount,
            'address' => $crypto->address,
            'status' => 'pending',
            'slip_path' => $path,
        ];

        // If this deposit is for VIP purchase, store vip metadata
        if ($request->filled('vip')) {
            $vip = strtoupper($request->input('vip'));
            $data['vip_level'] = $vip;
            $data['title'] = 'VIP Purchase - ' . $vip;
        }

        $deposit = Deposit::create($data);

        // Fire event for real-time updates
        broadcast(new DepositCreated($deposit))->toOthers();

        return redirect()->back()->with('success', 'Deposit submitted successfully! Awaiting approval.');
    }

    public function history(Request $request)
    {
        $query = Deposit::where('user_id', auth()->user()->id)
            ->orderBy('created_at', 'desc');

        // Add pagination
        $perPage = $request->get('per_page', 10); // Default 10, but allow more for initial load
        $deposits = $query->paginate($perPage);

        return response()->json([
            'deposits' => $deposits->items(),
            'pagination' => [
                'current_page' => $deposits->currentPage(),
                'last_page' => $deposits->lastPage(),
                'per_page' => $deposits->perPage(),
                'total' => $deposits->total(),
            ]
        ]);
    }

    public function adminIndex()
    {
        $deposits = Deposit::with('user')->get()->groupBy('user_id');
        return Inertia::render('Admin/DepositClients', [
            'deposits' => $deposits,
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $deposit = Deposit::findOrFail($id);
        $action = $request->input('action');

        if ($action === 'approve') {
            $user = User::find($deposit->user_id);
            $user->update(['balance' => $user->balance + $deposit->amount]);
            $deposit->update(['status' => 'approved']);

            // Create balance record for deposit
            BalanceRecord::create([
                'user_id' => $user->id,
                'type' => 'deposit',
                'amount' => $deposit->amount,
                'description' => $deposit->title ?: 'Deposit approved',
            ]);

            // If deposit is for VIP upgrade, update VIP level
            if ($deposit->vip_level) {
                $user->vip_level = $deposit->vip_level;
                $user->save();
            }
        } elseif ($action === 'reject') {
            $deposit->update(['status' => 'rejected']);
        }

        return redirect()->back()->with('success', 'Deposit status updated successfully.');
    }

    public function updateAddress(Request $request, $id)
    {
        $request->validate([
            'address' => 'required|string',
            'qr_code' => 'nullable|image|max:2048',
        ]);

        $deposit = Deposit::findOrFail($id);
        $data = ['address' => $request->address];

        if ($request->hasFile('qr_code')) {
            $path = $request->file('qr_code')->store('deposits/qr', 'public');
            $data['qr_code'] = $path;
        }

        $deposit->update($data);
        return redirect()->back()->with('success', 'Deposit address updated successfully.');
    }

    private function getCryptoDetails($symbol)
    {
        // Map symbol to CoinGecko ID
        $symbolToId = [
            'BTC' => 'bitcoin',
            'ETH' => 'ethereum',
            'USDT' => 'tether',
            'BNB' => 'binancecoin',
            'ADA' => 'cardano',
            'SOL' => 'solana',
            'DOT' => 'polkadot',
            'DOGE' => 'dogecoin',
            'AVAX' => 'avalanche-2',
            'LINK' => 'chainlink',
            'USDC' => 'usd-coin',
            'XRP' => 'xrp',
            'SHIB' => 'shiba-inu',
            'MATIC' => 'matic-network',
            'LTC' => 'litecoin',
        ];

        $id = $symbolToId[$symbol] ?? strtolower($symbol);

        try {
            $url = "https://api.coingecko.com/api/v3/coins/{$id}?localization=false&tickers=false&market_data=false&community_data=false&developer_data=false&sparkline=false";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Reduced timeout
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($response && $httpCode === 200) {
                $data = json_decode($response, true);
                if ($data) {
                    return [
                        'name' => $data['name'] ?? $symbol,
                        'image' => $data['image']['large'] ?? '',
                        'symbol' => $symbol,
                    ];
                }
            }
        } catch (\Exception $e) {
            \Log::warning('CoinGecko API failed for crypto details: ' . $e->getMessage());
        }

        // Fallback data
        $fallback = [
            'BTC' => ['name' => 'Bitcoin', 'image' => 'https://assets.coingecko.com/coins/images/1/large/bitcoin.png'],
            'ETH' => ['name' => 'Ethereum', 'image' => 'https://assets.coingecko.com/coins/images/279/large/ethereum.png'],
            'USDT' => ['name' => 'Tether', 'image' => 'https://assets.coingecko.com/coins/images/325/large/Tether.png'],
            'BNB' => ['name' => 'Binance Coin', 'image' => 'https://assets.coingecko.com/coins/images/825/large/bnb-icon2_2x.png'],
            'ADA' => ['name' => 'Cardano', 'image' => 'https://assets.coingecko.com/coins/images/975/large/cardano.png'],
            'SOL' => ['name' => 'Solana', 'image' => 'https://assets.coingecko.com/coins/images/4128/large/solana.png'],
            'DOT' => ['name' => 'Polkadot', 'image' => 'https://assets.coingecko.com/coins/images/12171/large/polkadot.png'],
            'DOGE' => ['name' => 'Dogecoin', 'image' => 'https://assets.coingecko.com/coins/images/5/large/dogecoin.png'],
            'AVAX' => ['name' => 'Avalanche', 'image' => 'https://assets.coingecko.com/coins/images/12559/large/coin-round-red.png'],
            'LINK' => ['name' => 'Chainlink', 'image' => 'https://assets.coingecko.com/coins/images/877/large/chainlink-new-logo.png'],
            'USDC' => ['name' => 'USD Coin', 'image' => 'https://assets.coingecko.com/coins/images/6319/large/USD_Coin_icon.png'],
            'XRP' => ['name' => 'XRP', 'image' => 'https://assets.coingecko.com/coins/images/44/large/xrp-symbol-white-128.png'],
            'SHIB' => ['name' => 'Shiba Inu', 'image' => 'https://assets.coingecko.com/coins/images/11939/large/shiba.png'],
            'MATIC' => ['name' => 'Polygon', 'image' => 'https://assets.coingecko.com/coins/images/4713/large/matic-token-icon.png'],
            'LTC' => ['name' => 'Litecoin', 'image' => 'https://assets.coingecko.com/coins/images/2/large/litecoin.png'],
        ];

        return $fallback[$symbol] ?? ['name' => $symbol, 'image' => '', 'symbol' => $symbol];
    }
}