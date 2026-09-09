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
        $locale = $request->getPreferredLanguage($supported);

        App::setLocale($locale ?? config(key: 'app.locale', default: 'en'));

        return $next($request);
    }
}
