<?php

namespace App\Http\Controllers;

use App\Models\Deposit;
use App\Models\User;
use App\Models\CryptoDepositDetail;
use App\Models\BalanceRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;


class DepositController extends Controller
{
   public function index(Request $request)
    {
        $detail = CryptoDepositDetail::first();
        $depositDetails = [
            'network' => $detail ? $detail->network : 'TBD',
            'address' => $detail ? $detail->address : 'TBD',
            'qr_code' => $detail ? $detail->qr_code : null,
            'currency' => $detail ? $detail->currency : '',
            'min_deposit' => $detail?->min_deposit ?? null,
            'deposit_account' => $detail?->deposit_account ?? null,
            'deposit_arrival_time' => $detail?->deposit_arrival_time ?? null,
            'withdraw_enabled_time' => $detail?->withdraw_enabled_time ?? null,
        ];

        // pass vip and prefill amount when coming from VIP purchase page
        $vip = $request->query('vip');
        $prefillAmount = $request->query('amount');

        return Inertia::render('Deposit', [
            'depositDetails' => $depositDetails,
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
            'amount' => 'required|numeric|min:1',
            'slip' => 'required|image|max:2048',
            'vip' => 'nullable|string',
        ]);

        $path = $request->file('slip')->store('deposits', 'public');
        $user = auth()->user();
        $detail = CryptoDepositDetail::first();

        $data = [
            'user_id' => $user->id,
            'symbol' => $detail ? strtolower($detail->currency) : '',
            'amount' => $request->amount,
            'address' => $detail ? $detail->address : 'TBD',
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

        return redirect()->back()->with('success', 'Deposit submitted successfully! Awaiting approval.');
    }

    public function history()
    {
    $deposits = Deposit::where('user_id', auth()->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json(['deposits' => $deposits]);
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

            // If deposit is for VIP upgrade, update VIP level and reassign tasks
            if ($deposit->vip_level) {
                $user->vip_level = $deposit->vip_level;
                $user->save();
                $user->assignTasks(); // <-- This will delete old tasks and assign new ones
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
}