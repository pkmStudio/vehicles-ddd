<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging\Handlers;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Brand\StartBrandMutationUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\BrandMutationRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogEntityEnum;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging\Validators\BrandMutationPayloadValidator;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging\WarehouseCatalogMutationContractMismatchReporter;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Принимает RabbitMQ-сообщение мутации Warehouse-брендов и запускает сценарий.
 */
final readonly class BrandMutationRequestedHandler
{
    /**
     * Инициализирует use case, factory и validator.
     */
    public function __construct(
        private StartBrandMutationUseCaseInterface $useCase,
        private BrandMutationPayloadValidator $validator,
        private WarehouseCatalogMutationContractMismatchReporter $contractMismatchReporter,
    ) {}

    /**
     * Валидирует payload, собирает DTO и запускает сценарий мутации бренда.
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
                message: 'RabbitMQ: Warehouse brand mutation payload validation failed',
                context: [
                    'invalid_keys' => $invalidKeys,
                ],
            );
            $this->contractMismatchReporter->report(WarehouseCatalogEntityEnum::Brand, $data, $invalidKeys);

            return;
        }

        $payload = $validator->validated();

        try {
            $requestDto = BrandMutationRequestDTO::fromArray($payload);
        } catch (Throwable $e) {
            Log::error('RabbitMQ: Warehouse brand mutation payload contract mismatch', [
                'operation_id' => $payload['operation_id'] ?? null,
                'exception' => $e,
            ]);
            $this->contractMismatchReporter->report(WarehouseCatalogEntityEnum::Brand, $payload, ['payload']);

            return;
        }

        $this->useCase->execute($requestDto);
    }
}
