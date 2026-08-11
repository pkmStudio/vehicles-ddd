<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\Handlers;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\Manufacturer\StartManufacturerMutationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Manufacturer\ManufacturerMutationRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\CatalogMutationContractMismatchReporter;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\Validators\ManufacturerMutationPayloadValidator;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Принимает RabbitMQ-сообщение мутации производителей и запускает сценарий.
 */
final readonly class ManufacturerMutationRequestedHandler
{
    /**
     * Инициализирует зависимости класса через контейнер.
     */
    public function __construct(
        private StartManufacturerMutationUseCaseInterface $useCase,
        private ManufacturerMutationPayloadValidator $validator,
        private CatalogMutationContractMismatchReporter $contractMismatchReporter,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): void
    {
        $validator = $this->validator->make($data);
        $validationFailed = $validator->fails();

        if ($validationFailed) {
            $invalidKeys = array_keys($validator->errors()->toArray());
            Log::error('RabbitMQ: Manufacturer mutation payload validation failed', [
                'invalid_keys' => $invalidKeys,
            ]);
            $this->contractMismatchReporter->report(CatalogEntityEnum::Manufacturer, $data, $invalidKeys);

            return;
        }

        $payload = $validator->validated();

        try {
            $request = ManufacturerMutationRequestDTO::fromArray($payload);
        } catch (Throwable $e) {
            Log::error('RabbitMQ: Manufacturer mutation payload contract mismatch', [
                'operation_id' => $payload['operation_id'] ?? null,
                'exception' => $e,
            ]);
            $this->contractMismatchReporter->report(CatalogEntityEnum::Manufacturer, $payload, ['payload']);

            return;
        }

        $this->useCase->execute($request);
    }
}
