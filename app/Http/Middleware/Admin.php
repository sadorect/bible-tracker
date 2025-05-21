<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
        /*
        if (!Auth::check() || !Auth::user()->isAdmin()) {
            return redirect()->route('dashboard')
                ->with('error', 'You do not have permission to access this area.');
        }
*/
Log::info('Admin middleware called'); // Add this line
if (Auth::check()) {
    Log::info('User is authenticated. User ID: ' . Auth::user()->id . ', Role: ' . Auth::user()->role); // Add this line
    if (!Auth::user()->isAdmin()) {
        Log::warning('User is not an admin. Redirecting...'); // Add this line
        return redirect()->route('dashboard')
            ->with('error', 'You do not have permission to access this area.');
    }
} else {
    Log::warning('User is not authenticated. Redirecting...'); // Add this line
    return redirect()->route('dashboard')
        ->with('error', 'You do not have permission to access this area.');
}
        return $next($request);
    }
}