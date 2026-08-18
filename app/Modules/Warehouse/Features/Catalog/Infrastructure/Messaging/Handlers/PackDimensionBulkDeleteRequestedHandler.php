<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging\Handlers;

use App\Modules\Warehouse\Features\Catalog\Application\UseCases\PackDimension\BulkDeletePackDimensionsUseCase;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\PackDimensionBulkDeleteRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging\Validators\PackDimensionBulkDeletePayloadValidator;
use Illuminate\Support\Facades\Log;
use PkmStudio\DanWireContracts\Vehicles\Modules\Warehouse\Features\Catalog\BulkDelete\DTO\PackDimensionBulkDeleteRequested as WirePackDimensionBulkDeleteRequested;
use Throwable;

/**
 * Принимает RabbitMQ-команду массового удаления упаковок.
 */
final readonly class PackDimensionBulkDeleteRequestedHandler
{
    /**
     * Получает use case bulk-delete и validator входящего сообщения.
     */
    public function __construct(
        private BulkDeletePackDimensionsUseCase $useCase,
        private PackDimensionBulkDeletePayloadValidator $validator,
    ) {}

    /**
     * Валидирует payload, собирает DTO и запускает bulk-delete сценарий.
     *
     * @param  array{user_id?: int|string|null, operation_id?: string|null, ids?: list<int|string>}  $data
     */
    public function handle(array $data): void
    {
        $validator = $this->validator->make($data);
        if ($validator->fails()) {
            Log::warning('RabbitMQ: Warehouse pack dimension bulk delete payload validation failed', [
                'operation_id' => is_string($data['operation_id'] ?? null) ? $data['operation_id'] : null,
                'invalid_keys' => array_keys($validator->errors()->toArray()),
            ]);

            return;
        }

        /** @var array{user_id: int|string, operation_id: string, ids: list<int|string>} $payload */
        $payload = $validator->validated();

        try {
            $wireRequest = WirePackDimensionBulkDeleteRequested::fromArray($payload);
            $this->useCase->execute(new PackDimensionBulkDeleteRequestDTO(
                userId: (int) $wireRequest->userId,
                operationId: $wireRequest->operationId,
                ids: $wireRequest->ids,
            ));
        } catch (Throwable $e) {
            Log::error('RabbitMQ: Warehouse pack dimension bulk delete failed', [
                'operation_id' => $payload['operation_id'],
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
