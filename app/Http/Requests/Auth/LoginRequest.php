<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class LoginRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'mobile_number' => ['required', 'string', 'regex:/^([0-9\s\-\+\(\)]*)$/'],
            'password' => ['required', 'string'],
        ];
    }

    public function authenticate()
    {
        $this->ensureIsNotRateLimited();

        $credentials = $this->only('mobile_number', 'password');
        $user = User::where('mobile_number', $credentials['mobile_number'])->first();

        // Check if user exists and password matches (plain text comparison)
        if (! $user || $user->password !== $credentials['password']) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'mobile_number' => trans('auth.failed'),
            ]);
        }

        // Log in the user manually
        Auth::login($user, $this->boolean('remember'));

        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited()
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'mobile_number' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey()
    {
        return Str::transliterate(Str::lower($this->input('mobile_number')).'|'.$this->ip());
    }
}