<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\Handlers;

use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\Modification\StartModificationMutationUseCase;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Modification\ModificationMutationRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\CatalogMutationContractMismatchReporter;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\Validators\ModificationMutationPayloadValidator;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Принимает RabbitMQ-сообщение мутации модификаций и запускает сценарий.
 */
final readonly class ModificationMutationRequestedHandler
{
    /**
     * Инициализирует зависимости класса через контейнер.
     *
     * Шаги:
     * 1. Получает use case мутации модификации.
     * 2. Получает validator входящего RabbitMQ payload.
     * 3. Получает reporter для contract mismatch результата.
     */
    public function __construct(
        private StartModificationMutationUseCase $useCase,
        private ModificationMutationPayloadValidator $validator,
        private CatalogMutationContractMismatchReporter $contractMismatchReporter,
    ) {}

    /**
     * Обрабатывает входящее RabbitMQ-сообщение мутации модификации.
     *
     * Шаги:
     * 1. Валидирует raw payload сообщения.
     * 2. Публикует failed-result при ошибке validation или несовместимом wire payload.
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
            Log::error('RabbitMQ: Modification mutation payload validation failed', [
                'invalid_keys' => $invalidKeys,
            ]);
            $this->contractMismatchReporter->report(CatalogEntityEnum::Modification, $data, $invalidKeys);

            return;
        }

        $payload = $validator->validated();

        try {
            $request = ModificationMutationRequestDTO::fromArray($payload);
        } catch (Throwable $e) {
            Log::error('RabbitMQ: Modification mutation payload contract mismatch', [
                'operation_id' => $payload['operation_id'] ?? null,
                'exception' => $e,
            ]);
            $this->contractMismatchReporter->report(CatalogEntityEnum::Modification, $payload, ['payload']);

            return;
        }

        $this->useCase->execute($request);
    }
}
