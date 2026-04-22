<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class Admin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->route('dashboard')
                ->with('error', 'You must be logged in to access this area.');
        }

        $user = Auth::user();

        if (! $user->canAccessAdminPanel()) {
            return redirect()->route('dashboard')
                ->with('error', 'You do not have permission to access this area.');
        }

        return $next($request);
    }
}
