<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Validators\EngineModification;

use App\Vehicles\Domain\Enums\VehicleTypeEnum;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final readonly class EngineModificationValidator
{
    /**
     * @throws ValidationException
     */
    public function validate(array $data): array
    {
        return Validator::make($data, [
            'eng_id' => ['required', 'integer'],
            'mod_id' => ['required', 'integer'],
            'type' => ['required', Rule::enum(VehicleTypeEnum::class)],
        ])->validate();
    }
}
