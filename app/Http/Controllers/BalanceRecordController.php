<?php

namespace App\Http\Controllers;

use App\Models\BalanceRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BalanceRecordController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        $records = BalanceRecord::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($record) {
                return [
                    'id' => $record->id,
                    'type' => $record->type,
                    'amount' => number_format($record->amount, 2, '.', ''),
                    'from_user_name' => $record->from_user_name,
                    'from_mobile_number' => $record->from_mobile_number,
                    'description' => $record->description,
                    'created_at' => $record->created_at,
                ];
            });

        if ($request->wantsJson()) {
            return response()->json(['records' => $records]);
        }

        return Inertia::render('BalanceRecord', [
            'records' => $records,
        ]);
    }
}
