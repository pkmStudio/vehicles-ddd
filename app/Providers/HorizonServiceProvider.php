<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Загружает Horizon application services и хуки notification routing.
     *
     * Шаги:
     * - Выполнить bootstrap базового Horizon service provider.
     * - Оставить notification routing disabled до явной настройки каналов.
     */
    public function boot(): void
    {
        parent::boot();

        // Horizon::routeSmsNotificationsTo('15556667777');
        // Horizon::routeMailNotificationsTo('example@example.com');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');
    }

    /**
     * Регистрирует Horizon gate для non-local окружений.
     *
     * This gate determines who can access Horizon in non-local environments.
     *
     * Шаги:
     * - Зарегистрировать Gate ability viewHorizon.
     * - Проверить email authenticated user against allow-list.
     * - Вернуть false для anonymous user и пустого allow-list.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null) {
            return in_array(optional($user)->email, [
                //
            ]);
        });
    }
}
