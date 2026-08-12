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
    /**
     * Получить Laravel validator factory.
     *
     * Шаги:
     * - Принять factory из container.
     * - Сохранить ее для создания validator instance на каждый payload.
     */
    public function __construct(
        private ValidatorFactory $validator,
    ) {}

    /**
     * Создать Laravel validator для полезной нагрузки события.
     *
     * Шаги:
     * - Описать обязательные поля внешнего export-запроса.
     * - Ограничить export_type поддерживаемыми enum-значениями.
     * - Вернуть validator без немедленного выполнения проверки.
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
     * Шаги:
     * - Взять все значения ExportTypeEnum.
     * - Преобразовать каждый enum case в строковое значение payload.
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
