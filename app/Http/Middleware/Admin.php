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
        Log::info('Admin middleware called');
        
        if (!Auth::check()) {
            Log::warning('User is not authenticated. Redirecting...');
            return redirect()->route('dashboard')
                ->with('error', 'You must be logged in to access this area.');
        }

        $user = Auth::user();
        Log::info('User is authenticated. User ID: ' . $user->id . ', Role: ' . $user->role);
        
        if (!$user->isAdmin()) {
            Log::warning('User is not an admin. Redirecting...');
            return redirect()->route('dashboard')
                ->with('error', 'You do not have permission to access this area.');
        }

        return $next($request);
    }
}