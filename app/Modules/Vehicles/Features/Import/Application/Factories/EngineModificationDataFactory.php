<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Factories;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories\EngineModificationDataFactoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\EngineModificationData;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Laravel Validator-адаптер, который валидирует строку и собирает EngineModificationData.
 */
final readonly class EngineModificationDataFactory implements EngineModificationDataFactoryInterface
{
    public function make(array $row): EngineModificationData
    {
        try {
            $valid = Validator::make($row, [
                'eng_id' => ['required', 'integer'],
                'mod_id' => ['required', 'integer'],
                'type' => ['required', Rule::enum(VehicleTypeEnum::class)],
            ])->validate();
        } catch (ValidationException $e) {
            throw ImportRowValidationException::fromMessages($e->errors());
        }

        return new EngineModificationData(
            engId: (int) $valid['eng_id'],
            modId: (int) $valid['mod_id'],
            type: VehicleTypeEnum::from((string) $valid['type']),
        );
    }
}
