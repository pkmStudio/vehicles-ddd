<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | Сервис не хранит локальных пользователей. Веб-guard оставлен только для
    | совместимости с пакетами Laravel, которые ожидают auth-конфиг.
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => null,
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'none',
        ],
    ],

    'providers' => [
        'none' => [
            'driver' => 'none',
        ],
    ],

    'passwords' => [],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
