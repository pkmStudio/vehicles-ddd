<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Domain\Services\Policy;

use App\Modules\Vehicles\Shared\Domain\DTOs\Policy\EngineWritePolicyResultDTO;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;

/**
 * Единое provider-aware правило записи двигателя для import и catalog mutation workflows.
 */
final readonly class EngineWritePolicy
{
    private const array BUSINESS_FIELDS = [
        'code_engine',
        'power_kw_start',
        'power_kw_upto',
        'power_ps_start',
        'power_ps_upto',
        'engine_capacity',
        'cylinder_diameter',
        'cylinder_count',
        'number_of_valves',
        'fuel_type',
    ];

    public function __construct(
        private ProviderOwnershipPolicy $ownership,
    ) {}

    /**
     * Применяет ownership/allow-change правила и возвращает готовый снимок для записи.
     *
     * Шаги:
     * 1) Для create назначить provider источника.
     * 2) Для update сохранить eng_id/id/provider/group_id существующей записи.
     * 3) Для same-provider или OD-owned записи применить все business fields.
     * 4) Для чужой provider-owned записи применить только пустые или allow_change_fields поля.
     */
    public function apply(
        EngineWritePolicyResultDTO $incoming,
        ?EngineWritePolicyResultDTO $existing,
        ProviderEnum $sourceProvider,
    ): EngineWritePolicyResultDTO {
        if ($existing === null) {
            return EngineWritePolicyResultDTO::fromArray([
                ...$incoming->toArray(),
                'provider' => $sourceProvider->value,
            ]);
        }

        $payload = $existing->toArray();
        $incomingBusiness = $this->only($incoming->toArray(), self::BUSINESS_FIELDS);

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
                    entityLabel: 'TD-двигателя',
                ),
            ];
        }

        return EngineWritePolicyResultDTO::fromArray([
            ...$payload,
            'eng_id' => $existing->engId,
            'provider' => $existing->provider->value,
            'group_id' => $existing->groupId,
            'id' => $existing->id,
        ]);
    }

    /**
     * Возвращает только разрешенные ключи массива.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $fields
     * @return array<string, mixed>
     */
    private function only(array $payload, array $fields): array
    {
        $result = [];

        foreach ($fields as $field) {
            $result[$field] = $payload[$field] ?? null;
        }

        return $result;
    }
}
