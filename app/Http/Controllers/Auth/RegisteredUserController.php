<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\BalanceRecord;
use App\Events\BalanceUpdated;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile_number' => ['required', 'string', 'max:255', 'unique:users', 'regex:/^([0-9\s\-\+\(\)]*)$/'],
            'password' => ['required', 'confirmed', 'min:6'],
            'invitation_code' => ['nullable', 'string', 'exists:users,invitation_code'],
            'withdraw_password' => ['required', 'string', 'min:6'],
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $request->name,
                'mobile_number' => $request->mobile_number,
                'password' => $request->password,
                'withdraw_password' => $request->withdraw_password,
                'invitation_code' => $this->generateUniqueInvitationCode(),
                'referred_by' => $request->invitation_code,
                'balance' => 0.00,
                'role' => 'user',
                'vip_level' => 'VIP1',
            ]);

            if ($request->invitation_code) {
                $inviter = User::where('invitation_code', $request->invitation_code)->first();
                if ($inviter) {
                    $inviter->balance += 10.00;
                    $inviter->save();
                    
                    // Create balance record for inviter
                    BalanceRecord::create([
                        'user_id' => $inviter->id,
                        'type' => 'invitation',
                        'amount' => 10.00,
                        'from_user_name' => $user->name,
                        'from_mobile_number' => $user->mobile_number,
                        'description' => 'Invitation bonus for referring new user',
                    ]);
                    
                    event(new BalanceUpdated($inviter));
                } else {
                    Log::warning('Inviter not found for invitation code', [
                        'invitation_code' => $request->invitation_code,
                    ]);
                }
            }

            event(new Registered($user));
            Auth::login($user);

            DB::commit();
            return redirect(route('dashboard', absolute: false));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User registration failed', [
                'error' => $e->getMessage(),
                'mobile_number' => $request->mobile_number,
            ]);
            return redirect()->back()->with('error', 'Registration failed. Please try again.');
        }
    }

    private function generateUniqueInvitationCode(): string
    {
        do {
            $code = Str::upper(Str::random(5));
        } while (User::where('invitation_code', $code)->exists());

        return $code;
    }
}