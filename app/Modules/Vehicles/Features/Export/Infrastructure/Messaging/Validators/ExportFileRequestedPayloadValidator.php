<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Messaging\Validators;

use App\Modules\Vehicles\Features\Export\Domain\Enums\ExportTypeEnum;
use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

/**
 * Валидирует payload входящего RabbitMQ-события запроса на экспорт каталога.
 */
final readonly class ExportFileRequestedPayloadValidator
{
    public function __construct(
        private ValidatorFactory $validator,
    ) {}

    /**
     * Создать Laravel validator для полезной нагрузки события.
     *
     * @param  array<string, mixed>  $data
     */
    public function make(array $data): Validator
    {
        return $this->validator->make($data, [
            'user_id' => ['required', 'integer', 'min:1'],
            'operation_id' => ['required', 'string', 'max:128'],
            'export_type' => ['required', 'string', Rule::in($this->exportTypes())],
            'is_allow' => ['sometimes', 'boolean'],
        ]);
    }

    /**
     * Вернуть допустимые значения типов экспорта.
     *
     * @return list<string>
     */
    private function exportTypes(): array
    {
        $toExportTypeValue = fn (ExportTypeEnum $type): string => $type->value;

        return array_map(
            $toExportTypeValue,
            ExportTypeEnum::cases(),
        );
    }
}
