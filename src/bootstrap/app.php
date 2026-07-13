<?php

declare(strict_types=1);

use App\Modules\Cart\Exceptions\CartLimitExceededException;
use App\Modules\Cart\Exceptions\CartItemNotFoundException;
use App\Modules\Order\Exceptions\EmptyCartException;
use App\Modules\Order\Exceptions\InvalidOrderTransitionException;
use App\Modules\Order\Exceptions\OrderNotFoundException;
use App\Modules\Order\Exceptions\OrderTooLargeException;
use App\Modules\User\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Http\Middleware\Authenticate as JwtAuthenticate;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'jwt.auth' => JwtAuthenticate::class,
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->renderable(function (CartLimitExceededException|EmptyCartException|OrderTooLargeException $e): Response {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => $e->getMessage(),
                'type' => class_basename(class: $e),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        });

        $exceptions->renderable(function (CartItemNotFoundException|OrderNotFoundException $e): Response {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => $e->getMessage(),
                'type' => class_basename(class: $e),
            ], Response::HTTP_NOT_FOUND);
        });

        $exceptions->renderable(function (InvalidOrderTransitionException $e): Response {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => $e->getMessage(),
                'type' => class_basename(class: $e),
            ], Response::HTTP_CONFLICT);
        });
    })
    ->create();
