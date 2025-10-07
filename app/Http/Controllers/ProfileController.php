<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        $user = User::findOrFail($request->user()->id); // Fresh data
        return Inertia::render('Profile/Edit', [
            'user' => $user,
        ]);
    }

    public function edit(Request $request)
    {
        $user = User::findOrFail($request->user()->id);
        return Inertia::render('Profile/EditProfile', [
            'user' => $user,
        ]);
    }

    /**
     * Update profile (works for avatar + other fields).
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'avatar_url' => ['sometimes', 'nullable', 'url', 'max:2000'],
            'invitation_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'mobile_number' => ['sometimes', 'nullable', 'string', 'max:50'],
        ]);

        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator)->withInput();
        }

        $user->fill($request->only([
            'name',
            'avatar_url',
            'invitation_code',
            'mobile_number'
        ]));

        $user->save();

        // Always redirect for Inertia
        return Redirect::route('profile.index')->with('success', 'Profile updated.');
    }

    public function destroy(Request $request)
    {
        $request->user()->delete();

        return Redirect::route('welcome');
    }

    /**
     * Upload and set avatar image.
     */
    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $user = $request->user();

        // Store under public disk for web access
        $dir = 'avatars/' . $user->id;
        // Optional: clean old files
        foreach (Storage::disk('public')->files($dir) as $old) {
            Storage::disk('public')->delete($old);
        }

        $path = $request->file('avatar')->store($dir, 'public');
        $url = Storage::url($path); // e.g., /storage/avatars/ID/filename.png

        $user->avatar_url = $url;
        $user->save();

        return Redirect::route('profile.index')->with('success', 'Avatar updated.');
    }
}
