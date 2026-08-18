<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging\Handlers;

use App\Modules\Warehouse\Features\Catalog\Application\UseCases\Kit\BulkDeleteKitsUseCase;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\KitBulkDeleteRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging\Validators\KitBulkDeletePayloadValidator;
use Illuminate\Support\Facades\Log;
use PkmStudio\DanWireContracts\Vehicles\Modules\Warehouse\Features\Catalog\BulkDelete\DTO\KitBulkDeleteRequested as WireKitBulkDeleteRequested;
use Throwable;

/**
 * Принимает RabbitMQ-команду массового удаления Warehouse-наборов.
 */
final readonly class KitBulkDeleteRequestedHandler
{
    /**
     * Получает use case bulk-delete и validator входящего сообщения.
     */
    public function __construct(
        private BulkDeleteKitsUseCase $useCase,
        private KitBulkDeletePayloadValidator $validator,
    ) {}

    /**
     * Валидирует payload, собирает DTO и запускает bulk-delete сценарий.
     *
     * @param  array{user_id?: int|string|null, operation_id?: string|null, ids?: list<int|string>}  $data
     */
    public function handle(array $data): void
    {
        $validator = $this->validator->make($data);
        $validationFailed = $validator->fails();

        if ($validationFailed) {
            Log::warning(
                message: 'RabbitMQ: Warehouse kit bulk delete payload validation failed',
                context: [
                    'operation_id' => is_string($data['operation_id'] ?? null) ? $data['operation_id'] : null,
                    'invalid_keys' => array_keys($validator->errors()->toArray()),
                ],
            );

            return;
        }

        /** @var array{user_id: int|string, operation_id: string, ids: list<int|string>} $payload */
        $payload = $validator->validated();

        try {
            $wireRequest = WireKitBulkDeleteRequested::fromArray($payload);
            $request = new KitBulkDeleteRequestDTO(
                userId: (int) $wireRequest->userId,
                operationId: $wireRequest->operationId,
                ids: $wireRequest->ids,
            );

            $this->useCase->execute($request);
        } catch (Throwable $e) {
            Log::error('RabbitMQ: Warehouse kit bulk delete failed', [
                'operation_id' => $payload['operation_id'],
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
