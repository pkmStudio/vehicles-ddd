<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\Auth\NullUserProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

final class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Auth::provider('none', fn () => new NullUserProvider);
    }
}
