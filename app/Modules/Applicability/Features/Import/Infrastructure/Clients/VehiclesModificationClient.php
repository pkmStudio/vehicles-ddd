<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Infrastructure\Clients;

use App\Modules\Applicability\Features\Import\Domain\Contracts\Clients\VehiclesModificationClientInterface;
use App\Modules\Applicability\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Shared\Domain\Contracts\Clients\VehiclesApplicabilityClientInterface;
use App\Modules\Vehicles\Shared\Domain\Exceptions\VehicleApplicabilityException;

/**
 * Адаптер публичного Vehicles API к import-порту Applicability.
 */
final readonly class VehiclesModificationClient implements VehiclesModificationClientInterface
{
    /**
     * Получает публичный read-only client Vehicles.
     *
     * Шаги:
     * 1. Сохраняет module-level Vehicles applicability client.
     * 2. Оставляет трансляцию ошибок во feature-local exception методу lookup.
     */
    public function __construct(
        private VehiclesApplicabilityClientInterface $vehicles,
    ) {}

    /**
     * Разрешает пару `ms_id`/`mod_id` в modification id для import-а применяемости.
     *
     * Шаги:
     * 1. Делегирует lookup публичному Vehicles client.
     * 2. Возвращает найденный local modification id.
     * 3. Переводит Vehicles lookup error в локальную row validation exception.
     */
    public function resolveByMsAndModId(int $msId, int $modId): int
    {
        try {
            return $this->vehicles->resolveModificationIdByMsAndModId($msId, $modId);
        } catch (VehicleApplicabilityException $exception) {
            throw new ImportRowValidationException($exception->getMessage(), previous: $exception);
        }
    }
}
