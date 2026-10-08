<?php

namespace App\Http\Middleware;

use App\Support\Google2FAAuthenticator;
use Closure;
use Illuminate\Support\Facades\Auth;

class LoginSecurityMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Suppliers skip 2FA setup (legacy behaviour; do not bounce between /2fa and /supplier-dashboard).
        if ($user->hasRole('supplier')) {
            return $next($request);
        }

        $authenticator = app(Google2FAAuthenticator::class)->boot($request);

        // Check if 2FA is enabled and if user is authenticated
        if ($user->loginSecurity && $user->loginSecurity->google2fa_enable) {
            if ($authenticator->isAuthenticated()) {
                return $next($request);
            }
            
            return $authenticator->makeRequestOneTimePasswordResponse();
        }

        // If 2FA is not enabled, redirect to 2FA settings page
        return redirect('/2fa');
    }
}
