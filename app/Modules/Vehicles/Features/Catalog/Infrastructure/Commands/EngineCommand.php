<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Commands;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\EngineCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\EngineData;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Engine;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Выполняет запись двигателей через Eloquent-модель фичи Catalog.
 */
final readonly class EngineCommand implements EngineCommandInterface
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

    /**
     * Создает запись двигателей.
     *
     * Шаги:
     * 1) Выполнить запись внутри транзакции.
     * 2) Вернуть актуальный Data-снимок созданной записи.
     */
    public function create(EngineData $data): EngineData
    {
        $createEngine = fn (): EngineData => EngineData::from(
            Engine::query()->create(Arr::except($data->toArray(), ['id'])),
        );

        return DB::transaction($createEngine);
    }

    /**
     * Обновляет запись двигателей.
     *
     * Шаги:
     * 1) Найти существующую запись внутри транзакции.
     * 2) Применить новые значения и сохранить модель.
     * 3) Вернуть актуальный Data-снимок записи.
     */
    public function update(EngineData $data): EngineData
    {
        return DB::transaction(function () use ($data): EngineData {
            $engine = Engine::query()->where('eng_id', $data->engId)->firstOrFail();
            $engine->fill($this->updatePayload($engine, $data));
            $engine->save();

            return EngineData::from($engine->refresh());
        });
    }

    /**
     * Удаляет запись двигателей по внешнему идентификатору.
     *
     * Шаги:
     * 1) Найти целевую запись по идентификатору.
     * 2) Удалить запись и зависимые записи внутри транзакции.
     */
    public function deleteByEngId(int $engId): void
    {
        DB::transaction(function () use ($engId): void {
            Engine::query()
                ->where('eng_id', $engId)
                ->delete();
        });
    }

    /**
     * Собирает payload обновления с учётом provider-правил.
     *
     * Шаги:
     * - Взять только бизнес-поля из входящего Data-снимка.
     * - Для provider OD разрешить запись всех бизнес-полей.
     * - Для остальных provider записать только пустые или явно разрешённые поля.
     *
     * @return array<string, mixed>
     */
    private function updatePayload(Engine $engine, EngineData $data): array
    {
        $incoming = Arr::only($data->toArray(), self::BUSINESS_FIELDS);

        if ($engine->provider === ProviderEnum::OD) {
            return [
                ...$incoming,
                'allow_change_fields' => $data->allowChangeFields,
            ];
        }

        $allowedFields = $this->stringList($engine->allow_change_fields ?? []);
        $payload = [];

        foreach ($incoming as $field => $value) {
            $current = $engine->getAttribute($field);
            if ($current === null || in_array($field, $allowedFields, true)) {
                $payload[$field] = $value;

                if ($current === null && $value !== null && $value !== '') {
                    $allowedFields[] = $field;
                }
            }
        }

        $payload['allow_change_fields'] = array_values(array_unique($allowedFields));

        return $payload;
    }

    /**
     * Нормализует список разрешённых к изменению полей.
     *
     * Шаги:
     * - Вернуть пустой список для не-массива.
     * - Оставить только scalar-значения и привести их к строкам.
     *
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (mixed $item): ?string => is_scalar($item) ? (string) $item : null,
            $value,
        )));
    }
}
