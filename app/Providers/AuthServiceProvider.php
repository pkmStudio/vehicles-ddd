<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\Auth\NullUserProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

final class AuthServiceProvider extends ServiceProvider
{
    /**
     * Регистрирует явный no-auth user provider для service routes.
     *
     * Шаги:
     * - Добавить provider alias none в Laravel Auth manager.
     * - Связать alias с NullUserProvider, который не восстанавливает пользователей.
     */
    public function boot(): void
    {
        Auth::provider('none', fn () => new NullUserProvider);
    }
}
