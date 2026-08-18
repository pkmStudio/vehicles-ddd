<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging\Validators;

use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Contracts\Validation\Validator;

/**
 * Строит Laravel-validator для RabbitMQ payload массового удаления Warehouse-наборов.
 */
final readonly class KitBulkDeletePayloadValidator
{
    /**
     * Получает фабрику validator'ов Laravel.
     */
    public function __construct(
        private ValidatorFactory $validator,
    ) {}

    /**
     * Создаёт validator с правилами wire-контракта bulk-delete kits command.
     *
     * @param  array{user_id?: int|string|null, operation_id?: string|null, ids?: list<int|string>}  $data
     */
    public function make(array $data): Validator
    {
        return $this->validator->make(
            data: $data,
            rules: [
                'user_id' => ['required', 'integer', 'min:1'],
                'operation_id' => ['required', 'string', 'max:128'],
                'ids' => ['required', 'array', 'min:1'],
                'ids.*' => ['required', 'integer', 'min:1', 'distinct'],
            ],
        );
    }
}
