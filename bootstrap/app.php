<?php

use App\Http\Middleware\EnsureAdultAccount;
use App\Http\Middleware\EnsureManagedLearner;
use App\Http\Middleware\EnsureSchoolContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectUsersTo(fn (Request $request): string => route('workspace'));
        $middleware->alias([
            'school.context' => EnsureSchoolContext::class,
            'adult.account' => EnsureAdultAccount::class,
            'learner.account' => EnsureManagedLearner::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
