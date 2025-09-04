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
    public function index()
    {
        $user = Auth::user();

        $balanceData = [
            // this project stores USDT balance in users.balance
            'usdt_balance' => $user->balance ?? 0,
        ];

        $withdrawals = Withdraw::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Return JSON for AJAX/fetch requests (not Inertia requests)
        if (($request = request())->wantsJson() || $request->ajax()) {
            if (!$request->header('X-Inertia')) {
                return response()->json([
                    'balances' => $balanceData,
                    'withdrawals' => $withdrawals,
                ]);
            }
        }

        // Render the Inertia page located at resources/js/Pages/Withdraw.vue
        return Inertia::render('Withdraw', [
            'balances' => $balanceData,
            'withdrawals' => $withdrawals,
        ]);
    }

    // submit withdraw (USDT only)
    public function store(Request $request)
    {
    $user = Auth::user();

        // If this is an AJAX validate-only request, just verify the withdraw password
        if (($request->wantsJson() || $request->ajax()) && !$request->header('X-Inertia') && $request->input('validate_only')) {
            $request->validate([
                'withdraw_password' => 'required|string',
            ]);

            if (!isset($user->withdraw_password) || $user->withdraw_password !== $request->input('withdraw_password')) {
                return response()->json(['errors' => ['withdraw_password' => ['Invalid withdraw password.']]], 422);
            }

            return response()->json(['success' => true]);
        }

        $validated = $request->validate([
            'amount_withdraw' => 'required|numeric|min:0.00000001',
            'crypto_wallet' => 'required|string|max:255', // recipient USDT address
            'withdraw_password' => 'required|string',
        ]);

        $amount = $validated['amount_withdraw'];

        if (($user->balance ?? 0) < $amount) {
            return redirect()->back()->with('error', 'Insufficient USDT balance for this withdrawal.');
        }

        // Verify withdraw password (stored as plain text per your request)
        if (!isset($user->withdraw_password) || $user->withdraw_password !== $request->input('withdraw_password')) {
            // If request comes from Inertia, throw a ValidationException so Inertia returns a proper Inertia response
            if ($request->header('X-Inertia')) {
                throw ValidationException::withMessages(['withdraw_password' => ['Invalid withdraw password.']]);
            }

            // For plain AJAX requests return JSON 422
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['errors' => ['withdraw_password' => ['Invalid withdraw password.']]], 422);
            }

            // Otherwise redirect back with an error message
            return redirect()->back()->with('error', 'Invalid withdraw password.');
        }

        // Create withdrawal request (pending)
        Withdraw::create([
            'user_id' => $user->id,
            'amount_withdraw' => $amount,
            'status' => 'pending',
            'crypto_wallet' => $validated['crypto_wallet'],
        ]);

        return redirect()->route('withdraw')->with('success', 'Withdrawal request submitted successfully. Await admin approval.');
    }
}