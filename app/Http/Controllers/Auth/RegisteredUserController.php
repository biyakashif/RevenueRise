<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
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
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'invitation_code' => ['nullable', 'string', 'exists:users,invitation_code'],
        ]);

        DB::beginTransaction();
        try {
            $initialBalance = $request->invitation_code ? 20.00 : 10.00;

            $user = User::create([
                'name' => $request->name,
                'mobile_number' => $request->mobile_number,
                'password' => $request->password,
                'invitation_code' => $this->generateUniqueInvitationCode(),
                'referred_by' => $request->invitation_code,
                'balance' => $initialBalance,
                'role' => 'user',
                'vip_level' => 'VIP1', // Set default VIP1
            ]);
            $user->assignTasks();

            if ($request->invitation_code) {
                $inviter = User::where('invitation_code', $request->invitation_code)->first();
                if ($inviter) {
                    $inviter->balance += 10.00;
                    $inviter->save();
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
            $code = Str::upper(Str::random(8));
        } while (User::where('invitation_code', $code)->exists());

        return $code;
    }
}