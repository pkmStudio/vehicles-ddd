<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Factories;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories\VehicleDataFactoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Vehicle\VehicleSheetRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Vehicle\VehicleTdRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\VehicleData;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\SteeringTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Laravel Validator-адаптер, который валидирует строку и собирает VehicleData.
 */
final readonly class VehicleDataFactory implements VehicleDataFactoryInterface
{
    /**
     * Этот метод валидирует строку vehicle import и собирает `VehicleData`.
     * Шаги:
     * 1) Нормализует входную строку, включая дефолт типа кузова для мотоциклов TecDoc.
     * 2) Валидирует нормализованные значения через Laravel Validator.
     * 3) Переводит scalar values в enum/value object поля `VehicleData`.
     *
     * @throws ImportRowValidationException
     */
    public function makeFromSheetRow(VehicleSheetRowDTO $row, int $msId, int $mfaId, int $manufacturerId, ?int $parentId): VehicleData
    {
        return $this->makeFromValues([
            'ms_id' => $msId,
            'mfa_id' => $mfaId,
            'name' => $row->name,
            'type' => $row->type,
            'type_carcase' => $row->typeCarcase,
            'steering_type' => $row->steeringType,
            'generation' => $row->generation,
            'generation_short' => $row->generationShort,
            'localized_name' => $row->localizedName,
            'excel_table_id' => $row->excelTableId,
            'provider' => $row->provider,
            'generation_year_from' => $row->generationYearFrom,
            'generation_year_to' => $row->generationYearTo,
            'is_allow' => $row->isAllow,
            'manufacturer_id' => $manufacturerId,
            'parent_id' => $parentId,
        ]);
    }

    /**
     * Этот метод валидирует строку TecDoc vehicle import и собирает `VehicleData`.
     *
     * Шаги:
     * 1) Собирает сценарные значения из typed DTO и найденного manufacturer id.
     * 2) Валидирует нормализованные значения через Laravel Validator.
     * 3) Переводит scalar values в enum/value object поля `VehicleData`.
     *
     * @throws ImportRowValidationException
     */
    public function makeFromTdRow(VehicleTdRowDTO $row, int $manufacturerId): VehicleData
    {
        return $this->makeFromValues([
            'ms_id' => $row->msId,
            'mfa_id' => $row->mfaId,
            'name' => $row->name,
            'type' => $row->type,
            'type_carcase' => $row->typeCarcase,
            'generation' => $row->generation,
            'generation_year_from' => $row->generationYearFrom,
            'generation_year_to' => $row->generationYearTo,
            'manufacturer_id' => $manufacturerId,
            'provider' => ProviderEnum::TD->value,
            'steering_type' => SteeringTypeEnum::LEFT->value,
            'is_allow' => false,
        ]);
    }

    /**
     * Этот метод валидирует подготовленные значения vehicle import и собирает `VehicleData`.
     *
     * Шаги:
     * 1) Нормализует входную строку, включая дефолт типа кузова для мотоциклов TecDoc.
     * 2) Валидирует нормализованные значения через Laravel Validator.
     * 3) Переводит scalar values в enum/value object поля `VehicleData`.
     *
     * @param  array<string, mixed>  $row
     *
     * @throws ImportRowValidationException
     */
    private function makeFromValues(array $row): VehicleData
    {
        try {
            $valid = Validator::make($row, [
                'ms_id' => ['required', 'integer'],
                'mfa_id' => ['required', 'integer'],
                'manufacturer_id' => ['required', 'integer'],
                'parent_id' => ['nullable', 'integer'],
                'name' => ['required'],
                'type' => ['required', Rule::enum(VehicleTypeEnum::class)],
                'type_carcase' => ['required', Rule::enum(CarcaseTypeEnum::class)],
                'steering_type' => ['required', Rule::enum(SteeringTypeEnum::class)],
                'generation' => ['required', 'string'],
                'generation_short' => ['nullable'],
                'localized_name' => ['nullable'],
                'excel_table_id' => ['nullable'],
                'provider' => ['required', Rule::enum(ProviderEnum::class)],
                'generation_year_from' => ['required', 'integer'],
                'generation_year_to' => ['nullable', 'integer'],
                'is_allow' => ['required', 'boolean'],
                'id' => ['nullable', 'integer'],
            ])->validate();
        } catch (ValidationException $e) {
            throw ImportRowValidationException::fromMessages($e->errors());
        }

        return new VehicleData(
            msId: (int) $valid['ms_id'],
            mfaId: (int) $valid['mfa_id'],
            manufacturerId: (int) $valid['manufacturer_id'],
            name: (string) $valid['name'],
            type: VehicleTypeEnum::from($valid['type']),
            steeringType: SteeringTypeEnum::from($valid['steering_type']),
            typeCarcase: CarcaseTypeEnum::from($valid['type_carcase']),
            provider: ProviderEnum::from($valid['provider']),
            generation: (string) $valid['generation'],
            generationYearFrom: (int) $valid['generation_year_from'],
            generationYearTo: isset($valid['generation_year_to']) ? (int) $valid['generation_year_to'] : null,
            parentId: isset($valid['parent_id']) ? (int) $valid['parent_id'] : null,
            excelTableId: isset($valid['excel_table_id']) ? (string) $valid['excel_table_id'] : null,
            localizedName: isset($valid['localized_name']) ? (string) $valid['localized_name'] : null,
            generationShort: isset($valid['generation_short']) ? (string) $valid['generation_short'] : null,
            isAllow: (bool) $valid['is_allow'],
            id: isset($valid['id']) ? (int) $valid['id'] : null,
        );
    }
}
