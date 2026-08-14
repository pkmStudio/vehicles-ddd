<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Factories;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories\ModificationDataFactoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Modification\ModificationSheetRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Modification\ModificationTdRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\ModificationData;
use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\BrakeSystemTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\DriveTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\GearTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Laravel Validator-адаптер, который валидирует строку и собирает ModificationData.
 */
final readonly class ModificationDataFactory implements ModificationDataFactoryInterface
{
    /**
     * Валидирует import-строку модификации и собирает typed `ModificationData`.
     *
     * Шаги:
     * 1) Проверить identifiers, годы, характеристики и enum-поля через Laravel Validator.
     * 2) Перевести validation errors в `ImportRowValidationException`.
     * 3) Привести валидные значения к типам конструктора `ModificationData`.
     *
     * @throws ImportRowValidationException
     */
    public function makeFromTdRow(ModificationTdRowDTO $row): ModificationData
    {
        return $this->makeFromValues($row->toArray());
    }

    /**
     * Валидирует manager DTO модификации и собирает typed `ModificationData`.
     *
     * Шаги:
     * 1) Собрать сценарные значения из typed DTO и resolved ids.
     * 2) Проверить identifiers, годы, характеристики и enum-поля через Laravel Validator.
     * 3) Привести валидные значения к типам конструктора `ModificationData`.
     *
     * @throws ImportRowValidationException
     */
    public function makeFromManagerSheetRow(ModificationSheetRowDTO $row, int $modId, string $type, int $vehicleId): ModificationData
    {
        return $this->makeFromValues([
            'mod_id' => $modId,
            'type' => $type,
            'vehicle_id' => $vehicleId,
            'ms_id' => $row->msId,
            'localized_name' => $row->localizedName,
            'year_from' => $row->yearFrom,
            'year_to' => $row->yearTo,
            'capacity_lt' => $row->capacityLt,
            'engine_type' => $row->engineType,
            'power_ps' => $row->powerPs,
            'power_kw' => $row->powerKw,
            'drive_type' => $row->driveType,
            'gear_type' => $row->gearType,
            'brake_system_type' => $row->brakeSystemType,
            'number_of_cylinders' => $row->numberOfCylinders,
            'description' => $row->description,
            'description_short' => $row->descriptionShort,
            'provider' => ProviderEnum::OD->value,
            'allow_change_fields' => ['year_from', 'year_to'],
        ]);
    }

    /**
     * Валидирует значения модификации и собирает typed `ModificationData`.
     *
     * Шаги:
     * 1) Проверить identifiers, годы, характеристики и enum-поля через Laravel Validator.
     * 2) Перевести validation errors в `ImportRowValidationException`.
     * 3) Привести валидные значения к типам конструктора `ModificationData`.
     *
     * @param  array<string, string|int|float|array<int, string>|null>  $row
     *
     * @throws ImportRowValidationException
     */
    private function makeFromValues(array $row): ModificationData
    {
        try {
            $valid = Validator::make($row, [
                'mod_id' => ['required', 'integer'],
                'type' => ['required', Rule::enum(VehicleTypeEnum::class)],
                'vehicle_id' => ['required', 'integer'],
                'ms_id' => ['required', 'integer'],
                'year_from' => ['required', 'integer'],
                'year_to' => ['nullable', 'integer'],
                'description' => ['required', 'string'],
                'description_short' => ['nullable'],
                'localized_name' => ['nullable'],
                'power_ps' => ['required', 'integer'],
                'power_kw' => ['required', 'integer'],
                'engine_type' => ['required', Rule::enum(EngineTypeEnum::class)],
                'gear_type' => ['nullable', Rule::enum(GearTypeEnum::class)],
                'drive_type' => ['nullable', Rule::enum(DriveTypeEnum::class)],
                'brake_system_type' => ['nullable', Rule::enum(BrakeSystemTypeEnum::class)],
                'number_of_cylinders' => ['nullable', 'integer'],
                'capacity_lt' => ['nullable', 'numeric'],
                'provider' => ['required', Rule::enum(ProviderEnum::class)],
                'allow_change_fields' => ['required', 'array'],
                'allow_change_fields.*' => ['string', 'max:64'],
                'id' => ['nullable', 'integer'],
            ])->validate();
        } catch (ValidationException $e) {
            throw ImportRowValidationException::fromMessages($e->errors());
        }

        return new ModificationData(
            modId: (int) $valid['mod_id'],
            type: VehicleTypeEnum::from($valid['type']),
            vehicleId: (int) $valid['vehicle_id'],
            msId: (int) $valid['ms_id'],
            provider: ProviderEnum::from((string) $valid['provider']),
            yearFrom: (int) $valid['year_from'],
            description: (string) $valid['description'],
            powerPs: (int) $valid['power_ps'],
            powerKw: (int) $valid['power_kw'],
            engineType: EngineTypeEnum::from($valid['engine_type']),
            yearTo: isset($valid['year_to']) ? (int) $valid['year_to'] : null,
            descriptionShort: isset($valid['description_short']) ? (string) $valid['description_short'] : null,
            localizedName: isset($valid['localized_name']) ? (string) $valid['localized_name'] : null,
            gearType: isset($valid['gear_type']) ? GearTypeEnum::from($valid['gear_type']) : null,
            driveType: isset($valid['drive_type']) ? DriveTypeEnum::from($valid['drive_type']) : null,
            brakeSystemType: isset($valid['brake_system_type']) ? BrakeSystemTypeEnum::from($valid['brake_system_type']) : null,
            numberOfCylinders: isset($valid['number_of_cylinders']) ? (int) $valid['number_of_cylinders'] : null,
            capacityLt: isset($valid['capacity_lt']) ? (float) $valid['capacity_lt'] : null,
            allowChangeFields: $valid['allow_change_fields'],
            id: isset($valid['id']) ? (int) $valid['id'] : null,
        );
    }
}
