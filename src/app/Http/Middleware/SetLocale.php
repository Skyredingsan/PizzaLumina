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
        $requested = $request->headers->get(key: 'Accept-Language');
        $locale = null;
        if (is_string(value: $requested) && trim(string: $requested) !== '') {
            $requested = strtolower(string: $requested);
            $locale = collect(value: preg_split(pattern: '/[,;]/', subject: $requested) ?: [])->map(callback: fn (string $value): string => strtolower(string: trim(string: explode(separator: '-', string: $value)[0])))->first(callback: fn (string $value): bool => in_array(needle: $value, haystack: $supported, strict: true));
        }
        App::setLocale($locale ?? config(key: 'app.locale', default: 'en'));
        return $next($request);
    }
}
