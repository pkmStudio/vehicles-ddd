<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Infrastructure\Messaging\Validators;

use App\Modules\Warehouse\Features\Import\Domain\Enums\ImportTypeEnum;
use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

/**
 * Строит Laravel validator для RabbitMQ payload запроса Warehouse-импорта.
 */
final readonly class ImportFileRequestedPayloadValidator
{
    /**
     * Получает Laravel validator factory.
     */
    public function __construct(
        private ValidatorFactory $validator,
    ) {}

    /**
     * Создаёт validator с правилами wire-контракта Warehouse-импорта.
     *
     * @param  array<string, mixed>  $data
     */
    public function make(array $data): Validator
    {
        return $this->validator->make(
            data: $data,
            rules: [
                'user_id' => ['required', 'integer', 'min:1'],
                'run_id' => ['required', 'string', 'max:128'],
                'import_type' => ['required', 'string', Rule::in($this->importTypes())],
                'path' => ['required', 'string', 'max:2048', 'not_regex:/\.\./', 'not_regex:/^\//'],
            ],
        );
    }

    /**
     * Возвращает допустимые значения import_type из enum, а не из строковых литералов.
     *
     * @return list<string>
     */
    private function importTypes(): array
    {
        $toImportTypeValue = fn (ImportTypeEnum $type): string => $type->value;

        return array_map(
            $toImportTypeValue,
            ImportTypeEnum::cases(),
        );
    }
}
