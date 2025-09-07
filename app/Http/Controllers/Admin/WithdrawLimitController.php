<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class WithdrawLimitController extends Controller
{
    public function update(Request $request, $userId)
    {
        $validated = $request->validate([
            'withdraw_limit' => 'required|numeric|min:0.00000001',
        ]);

        $user = User::findOrFail($userId);
        $user->withdraw_limit = $validated['withdraw_limit'];
        $user->save();

        if ($request->header('X-Inertia')) {
            Session::flash('success', 'Withdrawal limit updated successfully.');
            return back();
        }

        return response()->json([
            'success' => true,
            'message' => 'Withdrawal limit updated successfully',
            'user' => $user
        ]);
    }
}
