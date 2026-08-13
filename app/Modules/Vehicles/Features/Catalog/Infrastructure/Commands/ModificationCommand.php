<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Commands;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\ModificationCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\ModificationData;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Modification;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Выполняет запись модификаций через Eloquent-модель фичи Catalog.
 */
final readonly class ModificationCommand implements ModificationCommandInterface
{
    private const array BUSINESS_FIELDS = [
        'year_from',
        'year_to',
        'localized_name',
        'description',
        'power_ps',
        'power_kw',
        'brake_system_type',
        'engine_type',
        'gear_type',
        'drive_type',
        'number_of_cylinders',
        'capacity_lt',
    ];

    /**
     * Создает запись модификаций.
     *
     * Шаги:
     * 1) Выполнить запись внутри транзакции.
     * 2) Вернуть актуальный Data-снимок созданной записи.
     */
    public function create(ModificationData $data): ModificationData
    {
        $createModification = fn (): ModificationData => ModificationData::from(
            Modification::query()->create(Arr::except($data->toArray(), ['id'])),
        );

        return DB::transaction($createModification);
    }

    /**
     * Обновляет запись модификаций.
     *
     * Шаги:
     * 1) Найти существующую запись внутри транзакции.
     * 2) Применить новые значения и сохранить модель.
     * 3) Вернуть актуальный Data-снимок записи.
     */
    public function update(ModificationData $data): ModificationData
    {
        return DB::transaction(function () use ($data): ModificationData {
            $modification = Modification::query()
                ->where('mod_id', $data->modId)
                ->where('type', $data->type->value)
                ->firstOrFail();
            $modification->fill($this->updatePayload($modification, $data));
            $modification->save();

            return ModificationData::from($modification->refresh());
        });
    }

    /**
     * Удаляет запись модификаций по внешнему идентификатору.
     *
     * Шаги:
     * 1) Найти целевую запись по идентификатору.
     * 2) Удалить запись и зависимые записи внутри транзакции.
     */
    public function deleteByModIdAndType(int $modId, string $type): void
    {
        DB::transaction(function () use ($modId, $type): void {
            Modification::query()
                ->where('mod_id', $modId)
                ->where('type', $type)
                ->delete();
        });
    }

    /**
     * Удаляет модификации по внутренним id.
     *
     * Шаги:
     * - Пропустить пустой список id.
     * - В транзакции удалить найденные модификации.
     *
     * @param  array<int, int>  $ids
     */
    public function deleteByIds(array $ids): void
    {
        if ($ids === []) {
            return;
        }

        DB::transaction(function () use ($ids): void {
            Modification::query()->whereIn('id', $ids)->delete();
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
    private function updatePayload(Modification $modification, ModificationData $data): array
    {
        $incoming = Arr::only($data->toArray(), self::BUSINESS_FIELDS);

        if ($modification->provider === ProviderEnum::OD) {
            return [
                ...$incoming,
                'allow_change_fields' => $data->allowChangeFields,
            ];
        }

        $allowedFields = $modification->allow_change_fields;
        $payload = [];

        foreach ($incoming as $field => $value) {
            $current = $modification->getAttribute($field);
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
}
