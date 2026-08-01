<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin'         => \App\Http\Middleware\AdminOnly::class,
            'hearts'        => \App\Http\Middleware\EnsureUserHasHearts::class,
            'exam_enrolled' => \App\Http\Middleware\EnsureUserEnrolledInExamType::class,
            'throttle'      => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // Laravel doesn't log these by default (they're "expected" errors),
        // but every failed API response should still land in laravel.log.
        $exceptions->stopIgnoring([
            AuthenticationException::class,
            AuthorizationException::class,
            HttpException::class,
            ModelNotFoundException::class,
            TokenMismatchException::class,
            ValidationException::class,
        ]);

        // Keep expected client errors out of the "error" level to avoid
        // paging/alerting on 401/403/404/422s while still logging them.
        $exceptions->level(AuthenticationException::class, 'notice');
        $exceptions->level(AuthorizationException::class, 'notice');
        $exceptions->level(HttpException::class, 'notice');
        $exceptions->level(ModelNotFoundException::class, 'notice');
        $exceptions->level(ValidationException::class, 'notice');
    })->create();
