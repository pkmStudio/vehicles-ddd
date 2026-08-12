<?php

declare(strict_types=1);

namespace App\Support\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsureServiceKey
{
    /**
     * Пропускает service-to-service HTTP requests только при совпадении настроенного ключа.
     *
     * Шаги:
     * - Прочитать ожидаемый ключ из указанного config path.
     * - Пропустить проверку, если ключ не настроен для текущего окружения.
     * - Сравнить ожидаемый ключ с заголовком X-Service-Key constant-time сравнением.
     * - Передать запрос дальше или вернуть 401 JSON response.
     */
    public function handle(Request $request, Closure $next, string $configKey): Response
    {
        $key = (string) config($configKey, '');

        if ($key === '') {
            return $next($request);
        }

        if (hash_equals($key, (string) $request->header('X-Service-Key'))) {
            return $next($request);
        }

        return response()->json(['message' => 'Unauthorized.'], Response::HTTP_UNAUTHORIZED);
    }
}
