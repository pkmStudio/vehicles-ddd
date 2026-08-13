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
        $row = $this->normalizeRow($row);

        try {
            $valid = Validator::make($row, [
                'ms_id' => ['required', 'integer'],
                'mfa_id' => ['required', 'integer'],
                'manufacturer_id' => ['required', 'integer'],
                'parent_id' => ['nullable', 'integer'],
                'name' => ['required'],
                'type' => ['required', Rule::enum(VehicleTypeEnum::class)],
                'type_carcase' => ['required', Rule::enum(CarcaseTypeEnum::class)],
                'steering_type' => ['nullable', Rule::enum(SteeringTypeEnum::class)],
                'generation' => ['required', 'string'],
                'generation_short' => ['nullable'],
                'localized_name' => ['nullable'],
                'excel_table_id' => ['nullable'],
                'provider' => ['required', Rule::enum(ProviderEnum::class)],
                'generation_year_from' => ['required', 'integer'],
                'generation_year_to' => ['nullable', 'integer'],
                'is_allow' => ['sometimes', 'boolean'],
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
            steeringType: isset($valid['steering_type']) ? SteeringTypeEnum::from($valid['steering_type']) : SteeringTypeEnum::LEFT,
            typeCarcase: CarcaseTypeEnum::from($valid['type_carcase']),
            provider: ProviderEnum::from($valid['provider']),
            generation: (string) $valid['generation'],
            generationYearFrom: (int) $valid['generation_year_from'],
            generationYearTo: isset($valid['generation_year_to']) ? (int) $valid['generation_year_to'] : null,
            parentId: isset($valid['parent_id']) ? (int) $valid['parent_id'] : null,
            excelTableId: isset($valid['excel_table_id']) ? (string) $valid['excel_table_id'] : null,
            localizedName: isset($valid['localized_name']) ? (string) $valid['localized_name'] : null,
            generationShort: isset($valid['generation_short']) ? (string) $valid['generation_short'] : null,
            isAllow: (bool) ($valid['is_allow'] ?? false),
            id: isset($valid['id']) ? (int) $valid['id'] : null,
        );
    }

    /**
     * Этот метод применяет import-level defaults перед общей validation-схемой `VehicleData`.
     * Шаги:
     * 1) Скопировать входную строку без мутации аргумента вызывающего кода.
     * 2) Подставить type_carcase для мотоциклов, если источник не прислал тип кузова.
     * 3) Вернуть строку, готовую к валидации и сборке `VehicleData`.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizeRow(array $row): array
    {
        $type = $row['type'] ?? null;
        $typeCarcase = $row['type_carcase'] ?? null;

        $row['type_carcase'] = $this->defaultTypeCarcase(
            type: $type instanceof VehicleTypeEnum ? $type->value : ($type === null ? null : (string) $type),
            typeCarcase: $typeCarcase instanceof CarcaseTypeEnum ? $typeCarcase->value : ($typeCarcase === null ? null : (string) $typeCarcase),
        );

        return $row;
    }

    /**
     * Этот метод возвращает безопасный тип кузова для TecDoc-мотоциклов без исходного значения.
     * Шаги:
     * 1) Если `type_carcase` уже заполнен — вернуть его без изменений.
     * 2) Если тип ТС равен `MB` — вернуть `MOTORCYCLE`.
     * 3) Для остальных типов оставить исходное пустое значение, чтобы validator сообщил ошибку.
     */
    private function defaultTypeCarcase(?string $type, ?string $typeCarcase): ?string
    {
        if ($typeCarcase !== null && $typeCarcase !== '') {
            return $typeCarcase;
        }

        if ($type === VehicleTypeEnum::MB->value) {
            return CarcaseTypeEnum::MOTORCYCLE->value;
        }

        return $typeCarcase;
    }
}
