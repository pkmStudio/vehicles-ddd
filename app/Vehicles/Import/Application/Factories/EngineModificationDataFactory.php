<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Application\Factories;

use App\Vehicles\Import\Domain\Contracts\Factories\EngineModificationDataFactoryInterface;
use App\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
use App\Vehicles\Import\Domain\ModelData\EngineModification\EngineModificationData;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Валидирует сырую строку и собирает EngineModificationData (связь пивота).
 */
final readonly class EngineModificationDataFactory implements EngineModificationDataFactoryInterface
{
    /**
     * @throws ValidationException
     */
    public function make(array $row): EngineModificationData
    {
        $valid = Validator::make($row, [
            'eng_id' => ['required', 'integer'],
            'mod_id' => ['required', 'integer'],
            'type' => ['required', Rule::enum(VehicleTypeEnum::class)],
        ])->validate();

        return new EngineModificationData(
            engId: (int) $valid['eng_id'],
            modId: (int) $valid['mod_id'],
            type: (string) $valid['type'],
        );
    }
}
