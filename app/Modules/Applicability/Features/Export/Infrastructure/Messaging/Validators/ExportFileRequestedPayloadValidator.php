<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Infrastructure\Messaging\Validators;

use App\Modules\Applicability\Features\Export\Domain\Enums\ExportTypeEnum;
use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

final readonly class ExportFileRequestedPayloadValidator
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
     * Создает validator для запроса на export-файл применяемости.
     *
     * Шаги:
     * 1. Требует положительный `user_id`.
     * 2. Требует строковый `operation_id` ограниченной длины.
     * 3. Ограничивает `export_type` поддерживаемыми значениями enum.
     */
    public function make(array $data): Validator
    {
        return $this->validator->make($data, [
            'user_id' => ['required', 'integer', 'min:1'],
            'operation_id' => ['required', 'string', 'max:128'],
            'export_type' => ['required', 'string', Rule::in($this->exportTypes())],
        ]);
    }

    /**
     * Возвращает допустимые wire-значения типов export-файла.
     *
     * Шаги:
     * 1. Читает все cases локального `ExportTypeEnum`.
     * 2. Возвращает их scalar value для Laravel validation rule.
     */
    private function exportTypes(): array
    {
        return array_map(static fn (ExportTypeEnum $type): string => $type->value, ExportTypeEnum::cases());
    }
}
