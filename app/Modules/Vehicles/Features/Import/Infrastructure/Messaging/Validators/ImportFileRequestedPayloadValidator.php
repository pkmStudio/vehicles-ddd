<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Messaging\Validators;

use App\Modules\Vehicles\Features\Import\Domain\Enums\ExternalImportTypeEnum;
use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

/**
 * Валидирует payload входящего RabbitMQ-события импорта файла.
 */
final readonly class ImportFileRequestedPayloadValidator
{
    /**
     * Получить Laravel validator factory.
     *
     * Шаги:
     * 1) Принять validator factory из container.
     * 2) Сохранить ее для создания validator instance на каждый payload.
     */
    public function __construct(
        private ValidatorFactory $validator,
    ) {}

    /**
     * Создать Laravel validator для полезной нагрузки события.
     *
     * Шаги:
     * 1) Описать обязательные поля внешнего import request.
     * 2) Ограничить import_type поддерживаемыми enum-значениями.
     * 3) Проверить disk/path ограничения boundary payload.
     *
     * @param  array<string, mixed>  $data
     */
    public function make(array $data): Validator
    {
        return $this->validator->make($data, [
            'user_id' => ['required', 'integer', 'min:1'],
            'operation_id' => ['required', 'string', 'max:128'],
            'import_type' => ['required', 'string', Rule::in($this->importTypes())],
            'disk' => ['sometimes', 'string', Rule::in(array_keys((array) config('filesystems.disks', [])))],
            'path' => ['required', 'string', 'max:2048', 'not_regex:/\.\./', 'not_regex:/^\//'],
            'cleanup_after_import' => ['sometimes', 'boolean'],
        ]);
    }

    /**
     * Вернуть допустимые значения типов внешнего импорта.
     *
     * Шаги:
     * 1) Взять все cases ExternalImportTypeEnum.
     * 2) Преобразовать каждый case в строковое wire value.
     *
     * @return list<string>
     */
    private function importTypes(): array
    {
        $toImportTypeValue = fn (ExternalImportTypeEnum $type): string => $type->value;

        return array_map(
            $toImportTypeValue,
            ExternalImportTypeEnum::cases(),
        );
    }
}
