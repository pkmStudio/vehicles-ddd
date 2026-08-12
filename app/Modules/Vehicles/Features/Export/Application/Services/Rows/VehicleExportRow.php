<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Application\Services\Rows;

use App\Modules\Vehicles\Features\Export\Domain\Contracts\Services\Rows\VehicleExportRowInterface;
use App\Modules\Vehicles\Features\Export\Domain\ModelData\VehicleData;

/**
 * Формирует базовые Excel-ячейки автомобиля без шаблонных details.
 */
final readonly class VehicleExportRow implements VehicleExportRowInterface
{
    /**
     * Возвращает базовые заголовки для автомобиля.
     *
     * Шаги:
     * 1) Вернуть фиксированный список колонок основного vehicle-листа.
     */
    public function getBaseHeadings(): array
    {
        return [
            'ID Гугл таблицы',
            'ID Марки',
            'ID Модели',
            'Марка',
            'Модель',
            'Транслит',
            'Поколение (короткое)',
            'Полное поколение',
            'Года модели от',
            'Года модели до',
            'Тип кузова',
            'Тип транспорта',
            'Провайдер',
            'Родитель (ms_id)',
            'Рулевое управление',
            'Участвует в выборке',
        ];
    }

    /**
     * Возвращает базовые данные автомобиля.
     *
     * Шаги:
     * 1) Прочитать scalar, enum и eager-loaded relation поля из typed `VehicleData`.
     * 2) Вернуть значения в порядке базовых заголовков vehicle-листа.
     *
     * @param  VehicleData  $vehicle  Данные автомобиля (manufacturer/parent — если eager-loaded)
     */
    public function getBaseData(VehicleData $vehicle): array
    {
        return [
            $vehicle->excelTableId,
            $vehicle->mfaId,
            $vehicle->msId,
            $vehicle->manufacturer?->name,
            $vehicle->name,
            $vehicle->localizedName,
            $vehicle->generationShort,
            $vehicle->generation,
            $vehicle->generationYearFrom,
            $vehicle->generationYearTo,
            $vehicle->typeCarcase?->value,
            $vehicle->type->value,
            $vehicle->provider->value,
            $vehicle->parent?->msId,
            $vehicle->steeringType->value,
            $vehicle->isAllow ? 'Да' : 'Нет',
        ];
    }
}
