<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DeleteStatusMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if ($user && $user->status != 1) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->header('X-User-Spa') === '1'
                || $request->headers->has('X-Inertia')
                || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Your account has been blocked.',
                    'authenticated' => false,
                    'redirect' => '/login',
                    'csrf_token' => csrf_token(),
                ], 403);
            }

            return to_route('user.login');
        }

        return $next($request);
    }
}
