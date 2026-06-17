<?php

declare(strict_types=1);

namespace App\Vehicles\Traits;

use App\Vehicles\Models\Vehicle;

trait HasVehicleBaseData
{
    /**
     * Возвращает базовые заголовки для автомобиля
     */
    private function getBaseHeadings(): array
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
     * Возвращает базовые данные автомобиля
     *
     * @param  Vehicle  $vehicle  Модель автомобиля
     */
    private function getBaseData(Vehicle $vehicle): array
    {
        return [
            $vehicle->excel_table_id,
            $vehicle->mfa_id,
            $vehicle->ms_id,
            $vehicle->manufacturer->name ?? null,
            $vehicle->name,
            $vehicle->localized_name,
            $vehicle->generation_short,
            $vehicle->generation,
            $vehicle->generation_year_from,
            $vehicle->generation_year_to,
            $vehicle->type_carcase,
            $vehicle->type,
            $vehicle->provider,
            $vehicle->parent?->ms_id,
            $vehicle->steering_type,
            $vehicle->is_allow ? 'Да' : 'Нет',
        ];
    }
}
