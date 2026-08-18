<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging\Handlers;

use App\Modules\Warehouse\Features\Catalog\Application\UseCases\Brand\BulkDeleteBrandsUseCase;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\BrandBulkDeleteRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging\Validators\BrandBulkDeletePayloadValidator;
use Illuminate\Support\Facades\Log;
use PkmStudio\DanWireContracts\Vehicles\Modules\Warehouse\Features\Catalog\BulkDelete\DTO\BrandBulkDeleteRequested as WireBrandBulkDeleteRequested;
use Throwable;

/**
 * Принимает RabbitMQ-команду массового удаления брендов.
 */
final readonly class BrandBulkDeleteRequestedHandler
{
    /**
     * Получает use case bulk-delete и validator входящего сообщения.
     */
    public function __construct(
        private BulkDeleteBrandsUseCase $useCase,
        private BrandBulkDeletePayloadValidator $validator,
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
            Log::warning('RabbitMQ: Warehouse brand bulk delete payload validation failed', [
                'operation_id' => is_string($data['operation_id'] ?? null) ? $data['operation_id'] : null,
                'invalid_keys' => array_keys($validator->errors()->toArray()),
            ]);

            return;
        }

        /** @var array{user_id: int|string, operation_id: string, ids: list<int|string>} $payload */
        $payload = $validator->validated();

        try {
            $wireRequest = WireBrandBulkDeleteRequested::fromArray($payload);
            $this->useCase->execute(new BrandBulkDeleteRequestDTO(
                userId: (int) $wireRequest->userId,
                operationId: $wireRequest->operationId,
                ids: $wireRequest->ids,
            ));
        } catch (Throwable $e) {
            Log::error('RabbitMQ: Warehouse brand bulk delete failed', [
                'operation_id' => $payload['operation_id'],
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
