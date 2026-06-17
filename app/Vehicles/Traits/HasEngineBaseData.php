<?php

declare(strict_types=1);

namespace App\Vehicles\Traits;

use App\Vehicles\Models\Engine;

trait HasEngineBaseData
{
    private function getBaseHeadings(): array
    {
        return [
            'ID двигателя TecDoc',
            'Код двигателя',
            'Объём (куб. см)',
            'Тип топлива',
            'Мощность л.с. от',
            'Мощность л.с. до',
            'Кол-во цилиндров',
            'Диаметр цилиндров',
            'Кол-во клапанов',
        ];
    }

    private function getBaseData(Engine $engine): array
    {
        return [
            $engine->eng_id,
            $engine->code_engine,
            $engine->engine_capacity,
            $engine->eng_fuel_type,
            $engine->eng_power_ps_start,
            $engine->eng_power_ps_upto,
            $engine->cylinder_count,
            $engine->cylinder_diameter,
            $engine->eng_number_of_valves,
        ];
    }
}
