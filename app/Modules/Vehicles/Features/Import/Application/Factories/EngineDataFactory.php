<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Factories;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories\EngineDataFactoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Engine\EngineSheetRowDTO;
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
     * Валидирует строку по переданным правилам и собирает typed `EngineData`.
     *
     * Шаги:
     * 1) Запустить Laravel Validator с правилами конкретного сценария импорта.
     * 2) Перевести validation errors в доменное import-исключение.
     * 3) Собрать `EngineData` из валидированных значений.
     *
     * @throws ImportRowValidationException
     */
    public function make(EngineSheetRowDTO $row): EngineData
    {
        try {
            $valid = Validator::make($row->toArray(), $this->rules())->validate();
        } catch (ValidationException $e) {
            throw ImportRowValidationException::fromMessages($e->errors());
        }

        return new EngineData(
            engId: (int) $valid['eng_id'],
            provider: ProviderEnum::from((string) $valid['provider']),
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
            allowChangeFields: $valid['allow_change_fields'],
            id: isset($valid['id']) ? (int) $valid['id'] : null,
            groupId: isset($valid['group_id']) ? (int) $valid['group_id'] : null,
        );
    }

    /**
     * Возвращает validation rules для полного или частичного engine-листа.
     *
     * Шаги:
     * 1) Для полного TecDoc-листа требовать все характеристики.
     * 2) Для manager/main-листа оставить характеристики nullable.
     * 3) Всегда требовать provider и allow_change_fields из import-row DTO.
     *
     * @return array<string, array<int, mixed>>
     */
    private function rules(): array
    {
        return [
            'eng_id' => ['required', 'integer'],
            'code_engine' => ['nullable', 'string'],
            'power_kw_start' => ['nullable', 'integer'],
            'power_kw_upto' => ['nullable', 'integer'],
            'power_ps_start' => ['nullable', 'integer'],
            'power_ps_upto' => ['nullable', 'integer'],
            'engine_capacity' => ['nullable', 'string'],
            'cylinder_diameter' => ['nullable', 'numeric'],
            'cylinder_count' => ['nullable', 'integer'],
            'number_of_valves' => ['nullable', 'integer'],
            'fuel_type' => ['nullable', Rule::enum(EngineFuelTypeEnum::class)],
            'provider' => ['required', Rule::enum(ProviderEnum::class)],
            'allow_change_fields' => ['required', 'array'],
            'allow_change_fields.*' => ['string', 'max:64'],
            'id' => ['nullable', 'integer'],
            'group_id' => ['nullable', 'integer'],
        ];
    }
}
