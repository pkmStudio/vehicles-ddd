<?php

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
    ->withCommands([
        // Папки с командами вне стандартной app/Console/Commands (доменные/инфраструктурные).
        // Laravel рекурсивно найдёт в них все классы-команды. Добавляйте сюда новые папки.
        // rabbit-transport:setup регистрируется самим пакетом pkmstudio/rabbit-transport.
        __DIR__.'/../app/Modules/Vehicles/Features/Import/Presentation/Console/Commands',
        __DIR__.'/../app/Modules/Vehicles/Features/Export/Presentation/Console/Commands',
        __DIR__.'/../app/Modules/Vehicles/Features/Maintenance/Presentation/Console/Commands',
        __DIR__.'/../app/Modules/Warehouse/Features/Export/Presentation/Console/Commands',
        __DIR__.'/../app/Modules/Warehouse/Features/Import/Presentation/Console/Commands',
        __DIR__.'/../app/Modules/Warehouse/Features/Maintenance/Presentation/Console/Commands',
        __DIR__.'/../app/Modules/Warehouse/Features/MoySklad/Presentation/Console/Commands',
        __DIR__.'/../app/Modules/Applicability/Features/Calculation/Presentation/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
