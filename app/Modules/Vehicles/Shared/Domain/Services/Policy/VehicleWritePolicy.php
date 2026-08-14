<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Domain\Services\Policy;

use App\Modules\Vehicles\Shared\Domain\DTOs\VehicleWritePolicyResultDTO;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;

/**
 * Единое provider-aware правило записи автомобиля для import и catalog mutation workflows.
 */
final readonly class VehicleWritePolicy
{
    public function __construct(
        private ProviderOwnershipPolicy $ownership,
    ) {}

    /**
     * Применяет ownership правила и возвращает готовый снимок для записи.
     *
     * Шаги:
     * 1) Для create назначить provider источника.
     * 2) Для update запретить смену provider.
     * 3) Для update сохранить неизменяемые идентификаторы существующей записи.
     */
    public function apply(
        VehicleWritePolicyResultDTO $incoming,
        ?VehicleWritePolicyResultDTO $existing,
        ProviderEnum $sourceProvider,
    ): VehicleWritePolicyResultDTO {
        if ($existing === null) {
            return VehicleWritePolicyResultDTO::fromArray([
                ...$incoming->toArray(),
                'provider' => $sourceProvider->value,
            ]);
        }

        $this->ownership->assertSameProvider(
            existingProvider: $existing->provider,
            incomingProvider: $sourceProvider,
            entityLabel: 'ТС',
            externalId: $existing->msId,
        );

        return VehicleWritePolicyResultDTO::fromArray([
            ...$incoming->toArray(),
            'ms_id' => $existing->msId,
            'provider' => $existing->provider->value,
            'id' => $existing->id,
        ]);
    }

    /**
     * Возвращает true, если catalog mutation может резолвить и писать catalog-managed связи ТС.
     */
    public function allowsCatalogManagedFields(VehicleWritePolicyResultDTO $existing): bool
    {
        return $existing->provider === ProviderEnum::OD;
    }
}
