<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Infrastructure\Messaging\Validators;

use App\Modules\Warehouse\Features\Export\Domain\Enums\ExportTypeEnum;
use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

/**
 * Строит Laravel validator для RabbitMQ payload запроса Warehouse-экспорта.
 */
final readonly class ExportFileRequestedPayloadValidator
{
    /**
     * Получает фабрику validator'ов Laravel.
     */
    public function __construct(
        private ValidatorFactory $validator,
    ) {}

    /**
     * Создаёт validator с правилами wire-контракта Warehouse-экспорта.
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
                'export_type' => ['required', 'string', Rule::in($this->exportTypes())],
                'type_id' => [
                    Rule::requiredIf(($data['export_type'] ?? null) === ExportTypeEnum::NomenclatureByType->value),
                    'integer',
                    'min:1',
                ],
                'filters' => ['sometimes', 'array'],
                'filters.ids' => ['sometimes', 'array'],
                'filters.ids.*' => ['integer', 'min:1'],
                'filters.type_ids' => ['sometimes', 'array'],
                'filters.type_ids.*' => ['integer', 'min:1'],
                'filters.is_active' => ['sometimes', 'boolean'],
                'filters.is_sale_separately' => ['sometimes', 'boolean'],
                'filters.nomenclature_part_numbers' => ['sometimes', 'array'],
                'filters.nomenclature_part_numbers.*' => ['string', 'max:255'],
                'filters.search' => ['sometimes', 'nullable', 'string', 'max:255'],
                'sort' => ['sometimes', 'array'],
                'sort.field' => [
                    'sometimes',
                    'string',
                    Rule::in(['id', 'type_id', 'complectation', 'is_active', 'is_sale_separately']),
                ],
                'sort.direction' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
            ],
        );
    }

    /**
     * Возвращает допустимые значения export_type из enum, а не из строковых литералов.
     *
     * @return list<string>
     */
    private function exportTypes(): array
    {
        return array_map(
            fn (ExportTypeEnum $type): string => $type->value,
            ExportTypeEnum::cases(),
        );
    }
}
