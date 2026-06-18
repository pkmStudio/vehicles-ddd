<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Validators\Manufacturer;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Валидация сырых данных производителя перед сборкой DTO.
 */
final class ManufacturerValidator
{
    /**
     * @return array{mfa_id: int|string, name: string, provider: string}
     *
     * @throws ValidationException
     */
    public function validate(array $data): array
    {
        return Validator::make($data, [
            'mfa_id' => ['required', 'integer'],
            'name' => ['required', 'string'],
            'provider' => ['required', 'string'],
        ])->validate();
    }
}
