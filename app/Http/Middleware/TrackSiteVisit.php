<?php

namespace App\Http\Middleware;

use App\Models\SiteVisit;
use Closure;
use Illuminate\Http\Request;
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

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldTrack($request, $response)) {
            $this->record($request);
        }

        return $response;
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
            if (str_starts_with('/' . $path, $prefix)) {
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
                'url'        => substr($request->fullUrl(), 0, 512),
                'ip_address' => $request->ip(),
                'user_agent' => substr($request->userAgent() ?? '', 0, 512),
                'user_id'    => auth()->id(),
                'referrer'   => substr($request->headers->get('referer', ''), 0, 512),
            ]);
        } catch (\Throwable) {
            // Never let analytics break the app
        }
    }
}
