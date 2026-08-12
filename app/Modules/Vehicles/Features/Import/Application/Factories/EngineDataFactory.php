<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Factories;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories\EngineDataFactoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\EngineData;
use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineFuelTypeEnum;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Laravel Validator-адаптер, который валидирует строку и собирает EngineData.
 */
final readonly class EngineDataFactory implements EngineDataFactoryInterface
{
    /**
     * Валидирует import-строку двигателя и собирает typed `EngineData`.
     *
     * Шаги:
     * 1) Проверить scalar и enum-поля через Laravel Validator.
     * 2) Перевести validation errors в `ImportRowValidationException`.
     * 3) Привести валидные значения к типам конструктора `EngineData`.
     *
     * @param  array<string, mixed>  $row
     *
     * @throws ImportRowValidationException
     */
    public function make(array $row): EngineData
    {
        try {
            $valid = Validator::make($row, [
                'eng_id' => ['required', 'integer'],
                'code_engine' => ['nullable'],
                'power_kw_start' => ['nullable', 'integer'],
                'power_kw_upto' => ['nullable', 'integer'],
                'power_ps_start' => ['nullable', 'integer'],
                'power_ps_upto' => ['nullable', 'integer'],
                'engine_capacity' => ['nullable'],
                'cylinder_diameter' => ['nullable', 'numeric'],
                'cylinder_count' => ['nullable', 'integer'],
                'number_of_valves' => ['nullable', 'integer'],
                'fuel_type' => ['nullable', Rule::enum(EngineFuelTypeEnum::class)],
                'id' => ['nullable', 'integer'],
                'group_id' => ['nullable', 'integer'],
            ])->validate();
        } catch (ValidationException $e) {
            throw ImportRowValidationException::fromMessages($e->errors());
        }

        return new EngineData(
            engId: (int) $valid['eng_id'],
            codeEngine: isset($valid['code_engine']) ? (string) $valid['code_engine'] : null,
            powerKwStart: isset($valid['power_kw_start']) ? (int) $valid['power_kw_start'] : null,
            powerKwUpto: isset($valid['power_kw_upto']) ? (int) $valid['power_kw_upto'] : null,
            powerPsStart: isset($valid['power_ps_start']) ? (int) $valid['power_ps_start'] : null,
            powerPsUpto: isset($valid['power_ps_upto']) ? (int) $valid['power_ps_upto'] : null,
            engineCapacity: isset($valid['engine_capacity']) ? (string) $valid['engine_capacity'] : null,
            cylinderDiameter: isset($valid['cylinder_diameter']) ? (float) $valid['cylinder_diameter'] : null,
            cylinderCount: isset($valid['cylinder_count']) ? (int) $valid['cylinder_count'] : null,
            numberOfValves: isset($valid['number_of_valves']) ? (int) $valid['number_of_valves'] : null,
            fuelType: isset($valid['fuel_type']) ? EngineFuelTypeEnum::from($valid['fuel_type']) : null,
            id: isset($valid['id']) ? (int) $valid['id'] : null,
            groupId: isset($valid['group_id']) ? (int) $valid['group_id'] : null,
        );
    }
}
