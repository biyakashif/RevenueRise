<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', 'min:6'],
        ]);

        $user = Auth::user();

        // Check if current password matches (plain text comparison)
        if ($user->password !== $request->current_password) {
            return back()->withErrors([
                'current_password' => __('The provided password does not match your current password.'),
            ]);
        }

        // Update password as plain text
        $user->update([
            'password' => $request->password,
        ]);

        return back()->with('status', 'password-updated');
    }
}