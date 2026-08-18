<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\Handlers;

use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\Vehicle\BulkDeleteVehiclesUseCase;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\VehicleBulkDeleteRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\Validators\VehicleBulkDeletePayloadValidator;
use Illuminate\Support\Facades\Log;
use PkmStudio\DanWireContracts\Vehicles\Modules\Vehicles\Features\Catalog\BulkDelete\DTO\VehicleBulkDeleteRequested as WireVehicleBulkDeleteRequested;
use Throwable;

/**
 * Принимает RabbitMQ-команду массового удаления автомобилей.
 */
final readonly class VehicleBulkDeleteRequestedHandler
{
    /**
     * Получает use case bulk-delete и validator входящего сообщения.
     */
    public function __construct(
        private BulkDeleteVehiclesUseCase $useCase,
        private VehicleBulkDeletePayloadValidator $validator,
    ) {}

    /**
     * Валидирует payload, собирает DTO и запускает bulk-delete сценарий.
     *
     * @param  array{user_id?: int|string|null, operation_id?: string|null, ms_ids?: list<int|string>}  $data
     */
    public function handle(array $data): void
    {
        $validator = $this->validator->make($data);
        if ($validator->fails()) {
            Log::warning('RabbitMQ: vehicle bulk delete payload validation failed', [
                'operation_id' => is_string($data['operation_id'] ?? null) ? $data['operation_id'] : null,
                'invalid_keys' => array_keys($validator->errors()->toArray()),
            ]);

            return;
        }

        /** @var array{user_id: int|string, operation_id: string, ms_ids: list<int|string>} $payload */
        $payload = $validator->validated();

        try {
            $wireRequest = WireVehicleBulkDeleteRequested::fromArray($payload);
            $this->useCase->execute(new VehicleBulkDeleteRequestDTO(
                userId: (int) $wireRequest->userId,
                operationId: $wireRequest->operationId,
                msIds: $wireRequest->msIds,
            ));
        } catch (Throwable $e) {
            Log::error('RabbitMQ: vehicle bulk delete failed', [
                'operation_id' => $payload['operation_id'],
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
