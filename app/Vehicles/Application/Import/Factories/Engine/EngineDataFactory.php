<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Import\Factories\Engine;

use App\Vehicles\Domain\Contracts\Import\Factories\EngineDataFactoryInterface;
use App\Vehicles\Domain\Enums\EngineFuelTypeEnum;
use App\Vehicles\Domain\ModelData\Engine\EngineData;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Валидирует сырую строку и собирает EngineData.
 * eng_fuel_type валидируется как enum (сырое значение), маппинг — в casts модели.
 */
final readonly class EngineDataFactory implements EngineDataFactoryInterface
{
    /**
     * @throws ValidationException
     */
    public function make(array $row): EngineData
    {
        $valid = Validator::make($row, [
            'eng_id' => ['required', 'integer'],
            'code_engine' => ['nullable', 'string'],
            'eng_power_kw_start' => ['nullable', 'integer'],
            'eng_power_kw_upto' => ['nullable', 'integer'],
            'eng_power_ps_start' => ['nullable', 'integer'],
            'eng_power_ps_upto' => ['nullable', 'integer'],
            'engine_capacity' => ['nullable', 'string'],
            'cylinder_diameter' => ['nullable', 'numeric'],
            'cylinder_count' => ['nullable', 'integer'],
            'eng_number_of_valves' => ['nullable', 'integer'],
            'eng_fuel_type' => ['nullable', Rule::enum(EngineFuelTypeEnum::class)],
        ])->validate();

        return new EngineData(
            engId: (int) $valid['eng_id'],
            codeEngine: $valid['code_engine'] ?? null,
            engPowerKwStart: isset($valid['eng_power_kw_start']) ? (int) $valid['eng_power_kw_start'] : null,
            engPowerKwUpto: isset($valid['eng_power_kw_upto']) ? (int) $valid['eng_power_kw_upto'] : null,
            engPowerPsStart: isset($valid['eng_power_ps_start']) ? (int) $valid['eng_power_ps_start'] : null,
            engPowerPsUpto: isset($valid['eng_power_ps_upto']) ? (int) $valid['eng_power_ps_upto'] : null,
            engineCapacity: $valid['engine_capacity'] ?? null,
            cylinderDiameter: isset($valid['cylinder_diameter']) ? (float) $valid['cylinder_diameter'] : null,
            cylinderCount: isset($valid['cylinder_count']) ? (int) $valid['cylinder_count'] : null,
            engNumberOfValves: isset($valid['eng_number_of_valves']) ? (int) $valid['eng_number_of_valves'] : null,
            engFuelType: $valid['eng_fuel_type'] ?? null,
        );
    }
}
