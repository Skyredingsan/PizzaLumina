<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

final class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = ['ru', 'en'];
        $requestedLanguages = array_map(
            callback: static fn (string $locale): string => explode(separator: '_', string: $locale)[0],
            array: $request->getLanguages(),
        );
        $hasSupportedLanguage = array_intersect($supported, $requestedLanguages) !== [];
        $locale = $hasSupportedLanguage
            ? $request->getPreferredLanguage(locales: $supported)
            : null;

        App::setLocale($locale ?? config(key: 'app.locale', default: 'en'));

        return $next($request);
    }
}
