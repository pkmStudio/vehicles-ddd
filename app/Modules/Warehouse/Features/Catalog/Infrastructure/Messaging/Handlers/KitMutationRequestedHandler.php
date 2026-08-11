<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging\Handlers;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Kit\StartKitMutationUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\KitMutationRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogEntityEnum;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging\Validators\KitMutationPayloadValidator;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging\WarehouseCatalogMutationContractMismatchReporter;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Принимает RabbitMQ-сообщение мутации Warehouse-наборов и запускает сценарий.
 */
final readonly class KitMutationRequestedHandler
{
    /**
     * Инициализирует use case, factory и validator.
     */
    public function __construct(
        private StartKitMutationUseCaseInterface $useCase,
        private KitMutationPayloadValidator $validator,
        private WarehouseCatalogMutationContractMismatchReporter $contractMismatchReporter,
    ) {}

    /**
     * Валидирует payload, собирает DTO и запускает сценарий мутации набора.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): void
    {
        $validator = $this->validator->make($data);
        $validationFailed = $validator->fails();

        if ($validationFailed) {
            $invalidKeys = array_keys($validator->errors()->toArray());
            Log::error(
                message: 'RabbitMQ: Warehouse kit mutation payload validation failed',
                context: [
                    'invalid_keys' => $invalidKeys,
                ],
            );
            $this->contractMismatchReporter->report(WarehouseCatalogEntityEnum::Kit, $data, $invalidKeys);

            return;
        }

        $payload = $validator->validated();

        try {
            $requestDto = KitMutationRequestDTO::fromArray($payload);
        } catch (Throwable $e) {
            Log::error('RabbitMQ: Warehouse kit mutation payload contract mismatch', [
                'operation_id' => $payload['operation_id'] ?? null,
                'exception' => $e,
            ]);
            $this->contractMismatchReporter->report(WarehouseCatalogEntityEnum::Kit, $payload, ['payload']);

            return;
        }

        $this->useCase->execute($requestDto);
    }
}
