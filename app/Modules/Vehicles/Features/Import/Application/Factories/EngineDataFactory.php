<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Factories;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories\EngineDataFactoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Engine\EngineSheetRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Engine\EngineTdRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\EngineData;
use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineFuelTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Laravel Validator-адаптер, который валидирует строку и собирает EngineData.
 */
final readonly class EngineDataFactory implements EngineDataFactoryInterface
{
    /**
     * Валидирует TecDoc row DTO и собирает typed `EngineData`.
     *
     * Шаги:
     * 1) Передать payload TD DTO в общий builder.
     * 2) Требовать поля, которые TecDoc-файл и базовая миграция считают обязательными.
     *
     * @throws ImportRowValidationException
     */
    public function makeFromTdRow(EngineTdRowDTO $row): EngineData
    {
        return $this->makeFromValues($row->toArray());
    }

    /**
     * Валидирует manager/external sheet row DTO и собирает typed `EngineData`.
     *
     * Шаги:
     * 1) Передать payload sheet DTO в общий builder.
     * 2) Требовать поля, обязательные для записи engine.
     *
     * @throws ImportRowValidationException
     */
    public function makeFromSheetRow(EngineSheetRowDTO $row): EngineData
    {
        return $this->makeFromValues($row->toArray());
    }

    /**
     * Валидирует значения двигателя и собирает typed `EngineData`.
     *
     * @param  array<string, string|int|float|array<int, string>|null>  $row
     *
     * @throws ImportRowValidationException
     */
    private function makeFromValues(array $row): EngineData
    {
        try {
            $valid = Validator::make($row, $this->rules())->validate();
        } catch (ValidationException $e) {
            throw ImportRowValidationException::fromMessages($e->errors());
        }

        return new EngineData(
            engId: (int) $valid['eng_id'],
            provider: ProviderEnum::from((string) $valid['provider']),
            codeEngine: (string) $valid['code_engine'],
            powerKwStart: (int) $valid['power_kw_start'],
            powerPsStart: (int) $valid['power_ps_start'],
            fuelType: EngineFuelTypeEnum::from($valid['fuel_type']),
            powerKwUpto: isset($valid['power_kw_upto']) ? (int) $valid['power_kw_upto'] : null,
            powerPsUpto: isset($valid['power_ps_upto']) ? (int) $valid['power_ps_upto'] : null,
            engineCapacity: isset($valid['engine_capacity']) ? (string) $valid['engine_capacity'] : null,
            cylinderDiameter: isset($valid['cylinder_diameter']) ? (float) $valid['cylinder_diameter'] : null,
            cylinderCount: isset($valid['cylinder_count']) ? (int) $valid['cylinder_count'] : null,
            numberOfValves: isset($valid['number_of_valves']) ? (int) $valid['number_of_valves'] : null,
            allowChangeFields: $valid['allow_change_fields'],
            id: isset($valid['id']) ? (int) $valid['id'] : null,
            groupId: isset($valid['group_id']) ? (int) $valid['group_id'] : null,
        );
    }

    /**
     * Возвращает validation rules для engine-листа.
     *
     * Шаги:
     * 1) Требовать поля, которые не nullable в базовой миграции.
     * 2) Оставить nullable только те характеристики, которые реально пустуют в TecDoc.
     * 3) Всегда требовать provider и allow_change_fields из import-row DTO.
     *
     * @return array<string, array<int, mixed>>
     */
    private function rules(): array
    {
        return [
            'eng_id' => ['required', 'integer'],
            'code_engine' => ['required', 'string'],
            'power_kw_start' => ['required', 'integer'],
            'power_kw_upto' => ['nullable', 'integer'],
            'power_ps_start' => ['required', 'integer'],
            'power_ps_upto' => ['nullable', 'integer'],
            'engine_capacity' => ['nullable', 'string'],
            'cylinder_diameter' => ['nullable', 'numeric'],
            'cylinder_count' => ['nullable', 'integer'],
            'number_of_valves' => ['nullable', 'integer'],
            'fuel_type' => ['required', Rule::enum(EngineFuelTypeEnum::class)],
            'provider' => ['required', Rule::enum(ProviderEnum::class)],
            'allow_change_fields' => ['required', 'array'],
            'allow_change_fields.*' => ['string', 'max:64'],
            'id' => ['nullable', 'integer'],
            'group_id' => ['nullable', 'integer'],
        ];
    }
}
