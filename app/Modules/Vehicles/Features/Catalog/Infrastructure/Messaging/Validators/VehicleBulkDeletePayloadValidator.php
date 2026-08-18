<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\Validators;

use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Contracts\Validation\Validator;

/**
 * Строит Laravel-validator для RabbitMQ payload массового удаления автомобилей.
 */
final readonly class VehicleBulkDeletePayloadValidator
{
    /**
     * Получает фабрику validator'ов Laravel.
     */
    public function __construct(
        private ValidatorFactory $validator,
    ) {}

    /**
     * Создает validator с правилами wire-контракта bulk-delete vehicles command.
     *
     * @param  array{user_id?: int|string|null, operation_id?: string|null, ms_ids?: list<int|string>}  $data
     */
    public function make(array $data): Validator
    {
        return $this->validator->make($data, [
            'user_id' => ['required', 'integer', 'min:1'],
            'operation_id' => ['required', 'string', 'max:128'],
            'ms_ids' => ['required', 'array', 'min:1'],
            'ms_ids.*' => ['required', 'integer', 'distinct'],
        ]);
    }
}
