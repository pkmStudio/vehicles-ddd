<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\Validators;

use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Contracts\Validation\Validator;

/**
 * Строит Laravel-validator для RabbitMQ payload массового удаления двигателей.
 */
final readonly class EngineBulkDeletePayloadValidator
{
    /**
     * Получает фабрику validator'ов Laravel.
     */
    public function __construct(
        private ValidatorFactory $validator,
    ) {}

    /**
     * Создает validator с правилами wire-контракта bulk-delete engines command.
     *
     * @param  array{user_id?: int|string|null, operation_id?: string|null, eng_ids?: list<int|string>}  $data
     */
    public function make(array $data): Validator
    {
        return $this->validator->make($data, [
            'user_id' => ['required', 'integer', 'min:1'],
            'operation_id' => ['required', 'string', 'max:128'],
            'eng_ids' => ['required', 'array', 'min:1'],
            'eng_ids.*' => ['required', 'integer', 'distinct'],
        ]);
    }
}
