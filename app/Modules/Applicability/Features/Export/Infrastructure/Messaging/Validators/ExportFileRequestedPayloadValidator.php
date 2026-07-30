<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Infrastructure\Messaging\Validators;

use App\Modules\Applicability\Features\Export\Domain\Enums\ExportTypeEnum;
use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

final readonly class ExportFileRequestedPayloadValidator
{
    public function __construct(
        private ValidatorFactory $validator,
    ) {}

    public function make(array $data): Validator
    {
        return $this->validator->make($data, [
            'user_id' => ['required', 'integer', 'min:1'],
            'operation_id' => ['required', 'string', 'max:128'],
            'export_type' => ['required', 'string', Rule::in($this->exportTypes())],
        ]);
    }

    private function exportTypes(): array
    {
        return array_map(static fn (ExportTypeEnum $type): string => $type->value, ExportTypeEnum::cases());
    }
}
