<?php

declare(strict_types=1);

namespace App\Support\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;

final class NullUserProvider implements UserProvider
{
    /**
     * Отключает восстановление пользователя по постоянному идентификатору.
     *
     * Шаги:
     * - Игнорировать внешний идентификатор пользователя.
     * - Вернуть null, чтобы guard не восстановил authenticated user.
     */
    public function retrieveById($identifier): ?Authenticatable
    {
        return null;
    }

    /**
     * Отключает авторизацию через remember-token.
     *
     * Шаги:
     * - Игнорировать идентификатор и remember-token.
     * - Вернуть null, чтобы cookie/session token не авторизовал запрос.
     */
    public function retrieveByToken($identifier, #[\SensitiveParameter] $token): ?Authenticatable
    {
        return null;
    }

    /**
     * Оставляет хранилище remember-token без изменений для null-provider.
     *
     * Шаги:
     * - Принять contract call от Laravel guard.
     * - Не выполнять запись токена, потому что provider не хранит пользователей.
     */
    public function updateRememberToken(Authenticatable $user, #[\SensitiveParameter] $token): void {}

    /**
     * Отключает поиск пользователя по credentials.
     *
     * Шаги:
     * - Игнорировать переданные credentials.
     * - Вернуть null, чтобы попытка логина не нашла пользователя.
     */
    public function retrieveByCredentials(#[\SensitiveParameter] array $credentials): ?Authenticatable
    {
        return null;
    }

    /**
     * Отклоняет все проверки credentials для null-provider.
     *
     * Шаги:
     * - Принять user instance, переданный guard'ом.
     * - Вернуть false, чтобы credentials никогда не считались валидными.
     */
    public function validateCredentials(Authenticatable $user, #[\SensitiveParameter] array $credentials): bool
    {
        return false;
    }

    /**
     * Оставляет password hashes без изменений для null-provider.
     *
     * Шаги:
     * - Принять contract call после проверки credentials.
     * - Не выполнять rehash, потому что provider не управляет password storage.
     */
    public function rehashPasswordIfRequired(
        Authenticatable $user,
        #[\SensitiveParameter] array $credentials,
        bool $force = false,
    ): void {}
}
