<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Import\Factories\Manufacturer;

use App\Vehicles\Domain\Contracts\Import\Factories\ManufacturerDataFactoryInterface;
use App\Vehicles\Domain\ModelData\Manufacturer\ManufacturerData;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Валидирует сырую строку и собирает ManufacturerData.
 */
final readonly class ManufacturerDataFactory implements ManufacturerDataFactoryInterface
{
    /**
     * @throws ValidationException
     */
    public function make(array $row): ManufacturerData
    {
        $valid = Validator::make($row, [
            'mfa_id' => ['required', 'integer'],
            'name' => ['required', 'string'],
            'provider' => ['required', 'string'],
        ])->validate();

        return new ManufacturerData(
            mfaId: (int) $valid['mfa_id'],
            name: (string) $valid['name'],
            provider: (string) $valid['provider'],
        );
    }
}
