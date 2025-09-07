<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Withdraw;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class WithdrawController extends Controller
{
    // show all withdrawals grouped by user
    public function index(Request $request)
    {
        $search = $request->query('search', null);

        // Only include users who have withdrawals
        $query = User::whereHas('withdraws');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('mobile_number', 'like', "%{$search}%")
                  ->orWhere('invitation_code', 'like', "%{$search}%");
            });
        }

        // eager load withdrawals and order users by their latest withdraw
        $perPage = 10;
        // select first, then add the withMax subquery so the alias is included in the SELECT
        $users = $query
        ->select('id', 'name', 'mobile_number', 'invitation_code', 'withdraw_limit', 'balance')
        ->withMax('withdraws', 'created_at')
        ->orderByDesc('withdraws_max_created_at')
        ->with(['withdraws' => function ($q) {
            $q->orderBy('created_at', 'desc');
        }])
        ->paginate($perPage)
        ->appends($request->query());

        // If AJAX/JSON requested, return JSON for the frontend fetcher
        // Return JSON only for non-Inertia AJAX/fetch requests. Inertia requests send the X-Inertia header
        if (($request->wantsJson() || $request->ajax()) && !$request->header('X-Inertia')) {
            return response()->json($users);
        }

        return Inertia::render('Admin/Withdrawals', [
            'initialUsers' => $users->items(),
            'initialPage' => $users->currentPage(),
            'initialLastPage' => $users->lastPage(),
            'search' => $search,
        ]);
    }

    // approve a pending withdraw (subtract user usdt balance)
    public function approve(Request $request, $id)
    {
        $withdraw = Withdraw::findOrFail($id);

        if ($withdraw->status !== 'pending') {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['error' => 'This withdrawal has already been processed.'], 400);
            }
            return redirect()->back()->with('error', 'This withdrawal has already been processed.');
        }

        $user = $withdraw->user;

        // This project stores balance in users.balance (USDT)
        $amount = $withdraw->amount_withdraw;

        if (($user->balance ?? 0) < $amount) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['error' => 'Insufficient USDT balance to approve this withdrawal.'], 400);
            }
            return redirect()->back()->with('error', 'Insufficient USDT balance to approve this withdrawal.');
        }

        // Subtract from user's balance and save
        $user->balance = $user->balance - $amount;
        $user->save();

        $withdraw->status = 'approved';
        $withdraw->approved_at = now();
        $withdraw->save();

        if (($request->wantsJson() || $request->ajax()) && !$request->header('X-Inertia')) {
            return response()->json([
                'success' => true,
                'withdraw' => $withdraw,
                'user' => $user,
            ]);
        }

        return redirect()->back()->with('success', 'Withdrawal approved successfully.');
    }

    // reject a pending withdraw
    public function reject(Request $request, $id)
    {
        $withdraw = Withdraw::findOrFail($id);

        if ($withdraw->status !== 'pending') {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['error' => 'This withdrawal has already been processed.'], 400);
            }
            return redirect()->back()->with('error', 'This withdrawal has already been processed.');
        }

        $withdraw->status = 'rejected';
        $withdraw->rejected_at = now();
        $withdraw->save();

        if (($request->wantsJson() || $request->ajax()) && !$request->header('X-Inertia')) {
            return response()->json([
                'success' => true,
                'withdraw' => $withdraw,
            ]);
        }

        return redirect()->back()->with('success', 'Withdrawal rejected successfully.');
    }

    // edit view
    public function edit($id)
    {
        $withdraw = Withdraw::findOrFail($id);

        return Inertia::render('Admin/WithdrawEdit', [
            'withdraw' => $withdraw,
        ]);
    }

    // update withdraw (amount or wallet)
    public function update(Request $request, $id)
    {
        $withdraw = Withdraw::findOrFail($id);

        $validated = $request->validate([
            'amount_withdraw' => 'required|numeric|min:0.00000001',
            'crypto_wallet' => 'nullable|string|max:255',
        ]);

        $withdraw->amount_withdraw = $validated['amount_withdraw'];
        if (isset($validated['crypto_wallet'])) {
            $withdraw->crypto_wallet = $validated['crypto_wallet'];
        }
        $withdraw->save();

        return redirect()->route('admin.withdrawals')->with('success', 'Withdrawal updated successfully!');
    }
}