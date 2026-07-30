<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Infrastructure\Messaging\Validators;

use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Contracts\Validation\Validator;

final readonly class CalculationRequestedPayloadValidator
{
    public function __construct(
        private ValidatorFactory $validator,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function make(array $data): Validator
    {
        return $this->validator->make($data, [
            'user_id' => ['required', 'integer', 'min:1'],
            'operation_id' => ['required', 'string', 'max:128'],
            'kit_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'chunk' => ['sometimes', 'integer', 'min:1'],
        ]);
    }
}
