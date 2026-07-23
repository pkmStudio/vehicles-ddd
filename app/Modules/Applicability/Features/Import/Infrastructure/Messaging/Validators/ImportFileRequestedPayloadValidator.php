<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Infrastructure\Messaging\Validators;

use App\Modules\Applicability\Features\Import\Domain\Enums\ImportTypeEnum;
use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

final readonly class ImportFileRequestedPayloadValidator
{
    public function __construct(
        private ValidatorFactory $validator,
    ) {}

    public function make(array $data): Validator
    {
        return $this->validator->make($data, [
            'user_id' => ['required', 'integer', 'min:1'],
            'run_id' => ['required', 'string', 'max:128'],
            'import_type' => ['required', 'string', Rule::in($this->importTypes())],
            'path' => ['required', 'string', 'max:1024'],
        ]);
    }

    private function importTypes(): array
    {
        return array_map(static fn (ImportTypeEnum $type): string => $type->value, ImportTypeEnum::cases());
    }
}
