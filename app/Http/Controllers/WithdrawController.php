<?php

namespace App\Http\Controllers;

use App\Models\Withdraw;
use App\Models\CryptoDepositDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Validation\ValidationException;

class WithdrawController extends Controller
{
    // show withdraw page + history
    public function index(Request $request)
    {
        $user = Auth::user();

        $balanceData = [
            'usdt_balance' => number_format($user->balance ?? 0, 2, '.', ''),
            'withdraw_limit' => number_format($user->withdraw_limit ?? 30, 2, '.', ''),
        ];

        $withdrawals = Withdraw::where('user_id', $user->id)
            ->with('crypto')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($withdraw) {
                $withdraw->amount_withdraw = number_format($withdraw->amount_withdraw, 2, '.', '');
                if ($withdraw->crypto_amount && $withdraw->crypto_symbol) {
                    $withdraw->crypto_amount = $withdraw->crypto_symbol === 'USDT' 
                        ? number_format($withdraw->crypto_amount, 2, '.', '')
                        : number_format($withdraw->crypto_amount, 6, '.', '');
                }
                return $withdraw;
            });

        $data = [
            'balances' => $balanceData,
            'withdrawals' => $withdrawals,
        ];

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return Inertia::render('Withdraw', $data);
    }

    // submit withdraw (multi-crypto)
    public function store(Request $request)
    {
        try {
            $user = Auth::user();

            $validated = $request->validate([
                'amount_withdraw' => 'required|numeric|min:0.00000001',
                'crypto_symbol' => 'required|string',
                'crypto_amount' => 'required|numeric|min:0.00000001',
                'crypto_wallet' => 'required|string|max:255',
                'withdraw_password' => 'required|string',
            ]);

        $amount = $validated['amount_withdraw'];

        // Verify withdraw password
        if (!isset($user->withdraw_password) || $user->withdraw_password !== $validated['withdraw_password']) {
            throw ValidationException::withMessages([
                'withdraw_password' => ['Invalid withdraw password.']
            ]);
        }

        // Check balance
        if (($user->balance ?? 0) < $amount) {
            throw ValidationException::withMessages([
                'amount_withdraw' => ['Insufficient USDT balance. Available: ' . number_format(($user->balance ?? 0), 2) . ' USDT']
            ]);
        }

        // Check minimum withdrawal limit
        if ($amount < ($user->withdraw_limit ?? 30)) {
            throw ValidationException::withMessages([
                'amount_withdraw' => ['Minimum withdrawal amount is ' . number_format(($user->withdraw_limit ?? 30), 2) . ' USDT']
            ]);
        }

        // Deduct the amount from user balance
        $user->balance = ($user->balance ?? 0) - $amount;
        $user->save();

        // Create withdrawal request (under review)
        $withdraw = Withdraw::create([
            'user_id' => $user->id,
            'crypto_symbol' => $validated['crypto_symbol'],
            'crypto_amount' => $validated['crypto_amount'],
            'amount_withdraw' => $amount,
            'status' => 'under review',
            'crypto_wallet' => $validated['crypto_wallet'],
        ]);

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'success' => true,
                'message' => 'Withdrawal request submitted successfully',
                'withdraw' => $withdraw
            ]);
        }

        return redirect()->route('withdraw')
            ->with('success', 'Withdrawal request submitted successfully. Await admin approval.');
        } catch (ValidationException $e) {
            throw $e; // Re-throw validation exceptions to preserve error messages
        } catch (\Exception $e) {
            \Log::error('Withdrawal error: ' . $e->getMessage(), [
                'user_id' => $user->id ?? null,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->wantsJson() && !$request->header('X-Inertia')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 422);
            }
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    // show only withdrawal history
    public function history(Request $request)
    {
        $user = Auth::user();

        $withdrawals = Withdraw::where('user_id', $user->id)
            ->with('crypto')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($withdraw) {
                $withdraw->amount_withdraw = number_format($withdraw->amount_withdraw, 2, '.', '');
                if ($withdraw->crypto_amount && $withdraw->crypto_symbol) {
                    $withdraw->crypto_amount = $withdraw->crypto_symbol === 'USDT' 
                        ? number_format($withdraw->crypto_amount, 2, '.', '')
                        : number_format($withdraw->crypto_amount, 6, '.', '');
                }
                return $withdraw;
            });

        if ($request->wantsJson()) {
            return response()->json(['withdrawals' => $withdrawals]);
        }

        return Inertia::render('WithdrawHistory', [
            'withdrawals' => $withdrawals,
        ]);
    }
}