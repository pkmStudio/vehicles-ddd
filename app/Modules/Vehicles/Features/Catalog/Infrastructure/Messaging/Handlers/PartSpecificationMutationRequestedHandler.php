<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\Handlers;

use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\PartSpecification\StartPartSpecificationMutationUseCase;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Factories\PartSpecificationMutationRequestFactoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\CatalogMutationContractMismatchReporter;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\Validators\PartSpecificationMutationPayloadValidator;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Принимает RabbitMQ-сообщение мутации спецификаций деталей и запускает сценарий.
 */
final readonly class PartSpecificationMutationRequestedHandler
{
    /**
     * Инициализирует зависимости класса через контейнер.
     *
     * Шаги:
     * 1. Получает use case мутации спецификации детали.
     * 2. Получает factory локального DTO запроса.
     * 3. Получает validator входящего RabbitMQ payload.
     * 4. Получает reporter для contract mismatch результата.
     */
    public function __construct(
        private StartPartSpecificationMutationUseCase $useCase,
        private PartSpecificationMutationRequestFactoryInterface $factory,
        private PartSpecificationMutationPayloadValidator $validator,
        private CatalogMutationContractMismatchReporter $contractMismatchReporter,
    ) {}

    /**
     * Обрабатывает входящее RabbitMQ-сообщение мутации спецификаций деталей.
     *
     * Шаги:
     * 1. Валидирует raw payload сообщения.
     * 2. Публикует failed-result при ошибке validation или несовместимом wire payload.
     * 3. Собирает локальный DTO запроса через feature factory.
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
            Log::error('RabbitMQ: PartSpecification mutation payload validation failed', [
                'invalid_keys' => $invalidKeys,
            ]);
            $this->contractMismatchReporter->report(CatalogEntityEnum::PartSpecification, $data, $invalidKeys);

            return;
        }

        $payload = $validator->validated();

        try {
            $request = $this->factory->make($payload);
        } catch (Throwable $e) {
            Log::error('RabbitMQ: PartSpecification mutation payload contract mismatch', [
                'operation_id' => $payload['operation_id'] ?? null,
                'exception' => $e,
            ]);
            $this->contractMismatchReporter->report(CatalogEntityEnum::PartSpecification, $payload, ['payload']);

            return;
        }

        $this->useCase->execute($request);
    }
}
