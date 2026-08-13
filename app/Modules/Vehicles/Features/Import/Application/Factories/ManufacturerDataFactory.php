<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Factories;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories\ManufacturerDataFactoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\ManufacturerData;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Laravel Validator-адаптер, который валидирует строку и собирает ManufacturerData.
 */
final readonly class ManufacturerDataFactory implements ManufacturerDataFactoryInterface
{
    /**
     * Валидирует import-строку производителя и собирает typed `ManufacturerData`.
     *
     * Шаги:
     * 1) Проверить identifier, название и provider через Laravel Validator.
     * 2) Перевести validation errors в `ImportRowValidationException`.
     * 3) Привести валидные значения к типам конструктора `ManufacturerData`.
     *
     * @param  array<string, mixed>  $row
     *
     * @throws ImportRowValidationException
     */
    public function make(array $row): ManufacturerData
    {
        try {
            $valid = Validator::make($row, [
                'mfa_id' => ['required', 'integer'],
                'name' => ['required'],
                'provider' => ['required', Rule::enum(ProviderEnum::class)],
                'id' => ['nullable', 'integer'],
            ])->validate();
        } catch (ValidationException $e) {
            throw ImportRowValidationException::fromMessages($e->errors());
        }

        return new ManufacturerData(
            mfaId: (int) $valid['mfa_id'],
            name: (string) $valid['name'],
            provider: ProviderEnum::from($valid['provider']),
            id: isset($valid['id']) ? (int) $valid['id'] : null,
        );
    }
}
