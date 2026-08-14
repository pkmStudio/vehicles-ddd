<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\Handlers;

use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\Engine\StartEngineMutationUseCase;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\EngineMutationRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\CatalogMutationContractMismatchReporter;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\Validators\EngineMutationPayloadValidator;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Принимает RabbitMQ-сообщение мутации двигателей и запускает сценарий.
 */
final readonly class EngineMutationRequestedHandler
{
    /**
     * Инициализирует зависимости класса через контейнер.
     *
     * Шаги:
     * 1. Получает use case мутации двигателя.
     * 2. Получает validator входящего RabbitMQ payload.
     * 3. Получает reporter для contract mismatch результата.
     */
    public function __construct(
        private StartEngineMutationUseCase $useCase,
        private EngineMutationPayloadValidator $validator,
        private CatalogMutationContractMismatchReporter $contractMismatchReporter,
    ) {}

    /**
     * Обрабатывает входящее RabbitMQ-сообщение мутации двигателя.
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
            Log::error('RabbitMQ: Engine mutation payload validation failed', [
                'invalid_keys' => $invalidKeys,
            ]);
            $this->contractMismatchReporter->report(CatalogEntityEnum::Engine, $data, $invalidKeys);

            return;
        }

        $payload = $validator->validated();

        try {
            $request = EngineMutationRequestDTO::fromArray($payload);
        } catch (Throwable $e) {
            Log::error('RabbitMQ: Engine mutation payload contract mismatch', [
                'operation_id' => $payload['operation_id'] ?? null,
                'exception' => $e,
            ]);
            $this->contractMismatchReporter->report(CatalogEntityEnum::Engine, $payload, ['payload']);

            return;
        }

        $this->useCase->execute($request);
    }
}
