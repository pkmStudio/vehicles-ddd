<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Infrastructure\Commands;

use App\Modules\Warehouse\Features\Import\Domain\Contracts\Commands\NomenclatureCommandInterface;
use App\Modules\Warehouse\Features\Import\Domain\Exceptions\ImportPersistenceException;
use App\Modules\Warehouse\Features\Import\Domain\ModelData\NomenclatureData;
use App\Modules\Warehouse\Features\Import\Infrastructure\Models\Nomenclature;

/**
 * Пишет Warehouse-номенклатуру через Eloquent-копию модели Import-фичи.
 */
final readonly class NomenclatureCommand implements NomenclatureCommandInterface
{
    /**
     * Обновляет запись по id. Бросает исключение, если запись не найдена или артикул уже занят
     * другой записью (уникальный индекс part_number допускает конфликт только с самой собой).
     */
    public function updateById(NomenclatureData $data): NomenclatureData
    {
        $nomenclature = Nomenclature::query()->find($data->id);

        if ($nomenclature === null) {
            throw ImportPersistenceException::withMessage("Запись с ID {$data->id} не найдена");
        }

        $conflict = Nomenclature::query()
            ->where(
                column: 'part_number',
                operator: '=',
                value: $data->partNumber,
            )
            ->where(
                column: 'id',
                operator: '!=',
                value: $data->id,
            )
            ->first();

        if ($conflict !== null) {
            throw ImportPersistenceException::withMessage("Артикул '{$data->partNumber}' уже используется записью с ID {$conflict->id}");
        }

        $nomenclature->update($this->payload($data));

        return NomenclatureData::from($nomenclature->refresh());
    }

    /**
     * Создаёт новую номенклатуру. id не передаётся явно — колонка auto-increment, Postgres
     * назначит его сам (явный NULL в insert для serial-колонки — ошибка NOT NULL, поэтому id
     * здесь обязательно вырезается, в отличие от createWithId).
     */
    public function create(NomenclatureData $data): NomenclatureData
    {
        $payload = $this->payload($data);
        unset($payload['id']);

        $nomenclature = Nomenclature::query()->create($payload);

        return NomenclatureData::from($nomenclature);
    }

    /**
     * Создаёт новую номенклатуру с явно заданным id — для импорта строк из внешней системы,
     * где id уже назначен, но записи с ним ещё нет в этой БД.
     */
    public function createWithId(NomenclatureData $data): NomenclatureData
    {
        $nomenclature = Nomenclature::query()->create($this->payload($data));

        return NomenclatureData::from($nomenclature);
    }

    /**
     * Возвращает массив колонок для записи, исключая read-only relation `type`. id остаётся —
     * update и createWithId он нужен, а create() вырезает его отдельно сам.
     *
     * @return array<string, mixed>
     */
    private function payload(NomenclatureData $data): array
    {
        $payload = $data->toArray();
        unset($payload['type']);

        return $payload;
    }
}
