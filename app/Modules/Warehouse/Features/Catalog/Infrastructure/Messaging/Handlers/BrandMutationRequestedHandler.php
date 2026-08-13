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
     * Инициализирует use case, фабрику и validator.
     *
     * Шаги:
     * 1. Получает use case мутации бренда.
     * 2. Получает validator входящего RabbitMQ данные сообщения.
     * 3. Получает reporter для contract mismatch результата.
     */
    public function __construct(
        private StartBrandMutationUseCaseInterface $useCase,
        private BrandMutationPayloadValidator $validator,
        private WarehouseCatalogMutationContractMismatchReporter $contractMismatchReporter,
    ) {}

    /**
     * Валидирует данные сообщения, собирает DTO и запускает сценарий мутации бренда.
     *
     * Шаги:
     * 1. Валидирует raw данные сообщения сообщения.
     * 2. Публикует failed-result при ошибке validation или несовместимом wire данные сообщения.
     * 3. Собирает локальный DTO запроса из валидированных данных.
     * 4. Передает DTO во входной use case сценария.
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
