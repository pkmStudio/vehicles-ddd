<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging\Handlers;

use App\Modules\Warehouse\Features\Catalog\Application\UseCases\PackDimension\StartPackDimensionMutationUseCase;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\PackDimensionMutationRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogEntityEnum;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging\Validators\PackDimensionMutationPayloadValidator;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging\WarehouseCatalogMutationContractMismatchReporter;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Принимает RabbitMQ-сообщение мутации упаковочных размеров Warehouse и запускает сценарий.
 */
final readonly class PackDimensionMutationRequestedHandler
{
    /**
     * Инициализирует use case, фабрику и validator.
     *
     * Шаги:
     * 1. Получает use case мутации упаковочного размера.
     * 2. Получает validator входящего RabbitMQ данные сообщения.
     * 3. Получает reporter для contract mismatch результата.
     */
    public function __construct(
        private StartPackDimensionMutationUseCase $useCase,
        private PackDimensionMutationPayloadValidator $validator,
        private WarehouseCatalogMutationContractMismatchReporter $contractMismatchReporter,
    ) {}

    /**
     * Валидирует данные сообщения, собирает DTO и запускает сценарий мутации упаковочного размера.
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
                message: 'RabbitMQ: Warehouse pack dimension mutation payload validation failed',
                context: [
                    'invalid_keys' => $invalidKeys,
                ],
            );
            $this->contractMismatchReporter->report(WarehouseCatalogEntityEnum::PackDimension, $data, $invalidKeys);

            return;
        }

        $payload = $validator->validated();

        try {
            $requestDto = PackDimensionMutationRequestDTO::fromArray($payload);
        } catch (Throwable $e) {
            Log::error('RabbitMQ: Warehouse pack dimension mutation payload contract mismatch', [
                'operation_id' => $payload['operation_id'] ?? null,
                'exception' => $e,
            ]);
            $this->contractMismatchReporter->report(WarehouseCatalogEntityEnum::PackDimension, $payload, ['payload']);

            return;
        }

        $this->useCase->execute($requestDto);
    }
}
