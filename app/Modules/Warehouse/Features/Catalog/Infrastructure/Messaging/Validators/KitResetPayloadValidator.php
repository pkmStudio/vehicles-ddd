<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging\Validators;

use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Contracts\Validation\Validator;

/**
 * Строит Laravel validator для RabbitMQ payload запроса сброса Warehouse-наборов.
 */
final readonly class KitResetPayloadValidator
{
    /**
     * Получает фабрику validator'ов Laravel.
     */
    public function __construct(
        private ValidatorFactory $validator,
    ) {}

    /**
     * Создаёт validator с правилами wire-контракта reset-kits command.
     */
    public function make(array $data): Validator
    {
        return $this->validator->make(
            data: $data,
            rules: [
                'user_id' => ['required', 'integer', 'min:1'],
                'operation_id' => ['required', 'string', 'max:128'],
            ],
        );
    }
}
