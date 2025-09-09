<?php

namespace App\Http\Controllers;

use App\Models\Withdraw;
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
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($withdraw) {
                $withdraw->amount_withdraw = number_format($withdraw->amount_withdraw, 2, '.', '');
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

    // submit withdraw (USDT only)
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'amount_withdraw' => 'required|numeric|min:0.00000001',
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

        // Create withdrawal request (pending)
        $withdraw = Withdraw::create([
            'user_id' => $user->id,
            'amount_withdraw' => $amount,
            'status' => 'pending',
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
    }
}