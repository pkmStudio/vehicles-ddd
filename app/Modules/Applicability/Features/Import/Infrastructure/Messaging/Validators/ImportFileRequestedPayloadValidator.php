<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Infrastructure\Messaging\Validators;

use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;
use PkmStudio\DanWireContracts\Vehicles\Modules\Applicability\Features\Import\Enums\ApplicabilityImportType;

final readonly class ImportFileRequestedPayloadValidator
{
    /**
     * Получает Laravel validator factory для broker payload validation.
     *
     * Шаги:
     * 1. Сохраняет factory, создающую validator без запуска validation в constructor.
     * 2. Оставляет набор правил в методе `make()`.
     */
    public function __construct(
        private ValidatorFactory $validator,
    ) {}

    /**
     * Создает validator для запроса на импорт файла применяемости.
     *
     * Шаги:
     * 1. Требует положительный `user_id`.
     * 2. Требует строковый `operation_id` и поддерживаемый `import_type`.
     * 3. Принимает optional `disk` для обратной совместимости.
     * 4. Требует path исходного файла и принимает optional cleanup flag.
     *
     * @param  array{user_id?: int|string, operation_id?: string, import_type?: string, disk?: string, path?: string, cleanup_after_import?: bool|int|string}  $data
     */
    public function make(array $data): Validator
    {
        return $this->validator->make($data, [
            'user_id' => ['required', 'integer', 'min:1'],
            'operation_id' => ['required', 'string', 'max:128'],
            'import_type' => ['required', 'string', Rule::in($this->importTypes())],
            'disk' => ['sometimes', 'string', 'max:64'],
            'path' => ['required', 'string', 'max:1024'],
            'cleanup_after_import' => ['sometimes', 'boolean'],
        ]);
    }

    /**
     * Возвращает допустимые wire-значения типов import-файла.
     *
     * Шаги:
     * 1. Читает все cases package `ApplicabilityImportType`.
     * 2. Возвращает их scalar value для Laravel validation rule.
     *
     * @return list<string>
     */
    private function importTypes(): array
    {
        return array_map(static fn (ApplicabilityImportType $type): string => $type->value, ApplicabilityImportType::cases());
    }
}
