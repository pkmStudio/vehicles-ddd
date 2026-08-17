<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Domain\Services\Policy;

use App\Modules\Vehicles\Shared\Domain\DTOs\Policy\ModificationWritePolicyResultDTO;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Exceptions\ProviderOwnershipException;

/**
 * Единое provider-aware правило записи модификации для import и catalog mutation workflows.
 */
final readonly class ModificationWritePolicy
{
    private const array BUSINESS_FIELDS = [
        'year_from',
        'year_to',
        'localized_name',
        'description',
        'description_short',
        'power_ps',
        'power_kw',
        'brake_system_type',
        'engine_type',
        'gear_type',
        'drive_type',
        'number_of_cylinders',
        'capacity_lt',
    ];

    public function __construct(
        private ProviderOwnershipPolicy $ownership,
    ) {}

    /**
     * Применяет ownership/allow-change правила и возвращает готовый снимок для записи.
     *
     * Шаги:
     * 1) Для create назначить provider источника.
     * 2) Для update сохранить natural keys, связи, provider и id существующей записи.
     * 3) Для same-provider или OD-owned записи применить все business fields.
     * 4) Для чужой provider-owned записи применить только пустые или allow_change_fields поля.
     */
    public function apply(
        ModificationWritePolicyResultDTO $incoming,
        ?ModificationWritePolicyResultDTO $existing,
        ProviderEnum $sourceProvider,
    ): ModificationWritePolicyResultDTO {
        if ($existing === null) {
            return ModificationWritePolicyResultDTO::fromArray([
                ...$incoming->toArray(),
                'provider' => $sourceProvider->value,
            ]);
        }

        $payload = $existing->toArray();
        $incomingBusiness = $this->only($incoming->toArray());

        if ($existing->provider === $sourceProvider || $existing->provider === ProviderEnum::OD) {
            $payload = [
                ...$payload,
                ...$incomingBusiness,
                'allow_change_fields' => $incoming->allowChangeFields,
            ];
        } else {
            $payload = [
                ...$payload,
                ...$this->ownership->payload(
                    existingProvider: $existing->provider,
                    incomingProvider: $sourceProvider,
                    incoming: $incomingBusiness,
                    existingAllowChangeFields: $existing->allowChangeFields,
                    incomingAllowChangeFields: $incoming->allowChangeFields,
                    currentValue: static fn (string $field): string|int|float|null => $payload[$field] ?? null,
                    entityLabel: 'TD-модификации',
                ),
            ];
        }

        return ModificationWritePolicyResultDTO::fromArray([
            ...$payload,
            'mod_id' => $existing->modId,
            'type' => $existing->type->value,
            'vehicle_id' => $existing->vehicleId,
            'ms_id' => $existing->msId,
            'provider' => $existing->provider->value,
            'id' => $existing->id,
        ]);
    }

    /**
     * Запрещает менять TD-связи двигателей у TecDoc-модификации.
     *
     * Шаги:
     * 1) Для OD-модификаций разрешить изменение связей.
     * 2) Для TD-модификаций сравнить текущий и входящий набор TD eng_id.
     * 3) Разрешить добавление/удаление OD-двигателей поверх неизменного TD-набора.
     * 4) При отличии TD-набора выбросить provider ownership ошибку.
     *
     * @param  array<int, int>  $currentTdEngineIds
     * @param  array<int, int>  $incomingTdEngineIds
     */
    public function assertTdModificationEngineLinksUnchanged(
        ModificationWritePolicyResultDTO $existing,
        array $currentTdEngineIds,
        array $incomingTdEngineIds,
    ): void {
        if ($existing->provider !== ProviderEnum::TD) {
            return;
        }

        if ($this->engineIds($currentTdEngineIds) === $this->engineIds($incomingTdEngineIds)) {
            return;
        }

        throw ProviderOwnershipException::fromMessages([
            'engines' => ['TD-связи двигателей у модификации provider=TD нельзя изменять.'],
        ]);
    }

    /**
     * Возвращает только разрешенные ключи массива.
     *
     * @param  array<string, string|int|float|array<int, string>|null>  $payload
     * @return array<string, string|int|float|null>
     */
    private function only(array $payload): array
    {
        $result = [];

        foreach (self::BUSINESS_FIELDS as $field) {
            $result[$field] = $payload[$field] ?? null;
        }

        return $result;
    }

    /**
     * Нормализует список внешних eng_id для строгого сравнения наборов.
     *
     * @param  array<int, int>  $ids
     * @return array<int, int>
     */
    private function engineIds(array $ids): array
    {
        $ids = array_values(array_unique($ids));
        sort($ids);

        return $ids;
    }
}
