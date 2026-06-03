<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    /**
     * Where to redirect users after login (fallback only).
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Handle actions after the user is authenticated.
     */
    protected function authenticated(Request $request, $user)
    {
        // Company Admin
        if ($user->role === 'company_admin') {
            return redirect()->route('company.dashboard');
        }

        // Super Admin
        if ($user->role === 'super_admin') {
            return redirect()->route('admin.dashboard');
        }

        // Admin
        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        // Default fallback
        return redirect('/');
    }

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
    public function logout(Request $request)
{
    $user = $request->user();

    // ✅ mark offline immediately
    if ($user) {
        $user->forceFill([
            'last_seen_at' => now()->subHour(),
        ])->saveQuietly();
    }

    // default Laravel logout behavior
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/login');
}

}
