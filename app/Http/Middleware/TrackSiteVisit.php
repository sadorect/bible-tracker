<?php

namespace App\Http\Middleware;

use App\Models\SiteVisit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TrackSiteVisit
{
    /**
     * Paths to exclude from tracking.
     */
    private const EXCLUDED_PREFIXES = [
        '/livewire',
        '/api',
        '/_ignition',
        '/telescope',
        '/_debugbar',
    ];

    private const EXCLUDED_EXTENSIONS = [
        'js', 'css', 'ico', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'woff', 'woff2', 'ttf', 'map',
    ];

    /**
     * How long a users.last_online stamp stays fresh before it is rewritten.
     */
    private const PRESENCE_THROTTLE_SECONDS = 120;

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldTrack($request, $response)) {
            $this->record($request);
        }

        $this->stampPresence($request);

        return $response;
    }

    /**
     * Keep users.last_online current for signed-in users. Unlike the visit log
     * this covers every request method, and writes go through the query builder
     * so they never touch updated_at or fire model events.
     */
    private function stampPresence(Request $request): void
    {
        $user = $request->user();

        if (! $user) {
            return;
        }

        $lastOnline = $user->last_online;

        if ($lastOnline && $lastOnline->diffInSeconds(now()) < self::PRESENCE_THROTTLE_SECONDS) {
            return;
        }

        try {
            $now = now();

            DB::table('users')->where('id', $user->getKey())->update(['last_online' => $now]);

            $user->setAttribute('last_online', $now)->syncOriginalAttribute('last_online');
        } catch (\Throwable) {
            // Presence tracking must never break a request.
        }
    }

    private function shouldTrack(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        $path = $request->path();
        $ext = pathinfo($path, PATHINFO_EXTENSION);

        if ($ext && in_array(strtolower($ext), self::EXCLUDED_EXTENSIONS)) {
            return false;
        }

        foreach (self::EXCLUDED_PREFIXES as $prefix) {
            if (str_starts_with('/'.$path, $prefix)) {
                return false;
            }
        }

        // Only track successful HTML responses
        if (! in_array($response->getStatusCode(), [200, 301, 302])) {
            return false;
        }

        return true;
    }

    private function record(Request $request): void
    {
        try {
            SiteVisit::create([
                'session_id' => substr(session()->getId(), 0, 64),
                'url' => substr($request->fullUrl(), 0, 512),
                'ip_address' => $request->ip(),
                'user_agent' => substr($request->userAgent() ?? '', 0, 512),
                'user_id' => auth()->id(),
                'referrer' => substr($request->headers->get('referer', ''), 0, 512),
            ]);
        } catch (\Throwable) {
            // Never let analytics break the app
        }
    }
}
