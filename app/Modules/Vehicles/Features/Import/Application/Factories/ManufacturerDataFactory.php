<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Factories;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories\ManufacturerDataFactoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\ManufacturerData;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Валидирует контракт данных и собирает ManufacturerData.
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
            'name' => ['required'],
            'provider' => ['required', Rule::enum(ProviderEnum::class)],
        ])->validate();

        return new ManufacturerData(
            mfaId: (int) $valid['mfa_id'],
            name: (string) $valid['name'],
            provider: ProviderEnum::from($valid['provider']),
        );
    }
}
