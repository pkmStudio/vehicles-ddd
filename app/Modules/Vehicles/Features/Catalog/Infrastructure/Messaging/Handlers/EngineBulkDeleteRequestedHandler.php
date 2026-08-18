<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\Handlers;

use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\Engine\BulkDeleteEnginesUseCase;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\EngineBulkDeleteRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\Validators\EngineBulkDeletePayloadValidator;
use Illuminate\Support\Facades\Log;
use PkmStudio\DanWireContracts\Vehicles\Modules\Vehicles\Features\Catalog\BulkDelete\DTO\EngineBulkDeleteRequested as WireEngineBulkDeleteRequested;
use Throwable;

/**
 * Принимает RabbitMQ-команду массового удаления двигателей.
 */
final readonly class EngineBulkDeleteRequestedHandler
{
    /**
     * Получает use case bulk-delete и validator входящего сообщения.
     */
    public function __construct(
        private BulkDeleteEnginesUseCase $useCase,
        private EngineBulkDeletePayloadValidator $validator,
    ) {}

    /**
     * Валидирует payload, собирает DTO и запускает bulk-delete сценарий.
     *
     * @param  array{user_id?: int|string|null, operation_id?: string|null, eng_ids?: list<int|string>}  $data
     */
    public function handle(array $data): void
    {
        $validator = $this->validator->make($data);
        if ($validator->fails()) {
            Log::warning('RabbitMQ: engine bulk delete payload validation failed', [
                'operation_id' => is_string($data['operation_id'] ?? null) ? $data['operation_id'] : null,
                'invalid_keys' => array_keys($validator->errors()->toArray()),
            ]);

            return;
        }

        /** @var array{user_id: int|string, operation_id: string, eng_ids: list<int|string>} $payload */
        $payload = $validator->validated();

        try {
            $wireRequest = WireEngineBulkDeleteRequested::fromArray($payload);
            $this->useCase->execute(new EngineBulkDeleteRequestDTO(
                userId: (int) $wireRequest->userId,
                operationId: $wireRequest->operationId,
                engIds: $wireRequest->engIds,
            ));
        } catch (Throwable $e) {
            Log::error('RabbitMQ: engine bulk delete failed', [
                'operation_id' => $payload['operation_id'],
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
