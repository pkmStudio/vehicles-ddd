<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging\Handlers;

use App\Modules\Warehouse\Features\Catalog\Application\UseCases\Kit\ResetKitsUseCase;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\KitResetRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging\Validators\KitResetPayloadValidator;
use Illuminate\Support\Facades\Log;

/**
 * Принимает RabbitMQ-команду сброса Warehouse-наборов.
 */
final readonly class KitResetRequestedHandler
{
    /**
     * Получает use case сброса и validator входящего сообщения.
     */
    public function __construct(
        private ResetKitsUseCase $useCase,
        private KitResetPayloadValidator $validator,
    ) {}

    /**
     * Валидирует payload и запускает bulk-сброс Warehouse-наборов.
     */
    public function handle(array $data): void
    {
        $validator = $this->validator->make($data);
        $validationFailed = $validator->fails();

        if ($validationFailed) {
            Log::error(
                message: 'RabbitMQ: Warehouse kits reset payload validation failed',
                context: [
                    'invalid_keys' => array_keys($validator->errors()->toArray()),
                ],
            );

            return;
        }

        $payload = $validator->validated();
        $request = new KitResetRequestDTO(
            userId: (int) $payload['user_id'],
            operationId: (string) $payload['operation_id'],
        );

        $this->useCase->execute($request);
    }
}
