<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'mobile_number' => $request->user()->mobile_number,
                    'invitation_code' => $request->user()->invitation_code,
                    'balance' => (float) $request->user()->balance,
                    // include vip_level so frontend can correctly mark current VIP
                    'vip_level' => $request->user()->vip_level,
                    // include role in case you need admin/user checks client-side
                    'role' => $request->user()->role ?? 'user',
                    // include avatar_url for profile and chat display
                    'avatar_url' => $request->user()->role === 'admin' ? '/assets/avatar/admin.png' : $request->user()->avatar_url,
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'csrf_token' => csrf_token(),
        ]);
    }
}