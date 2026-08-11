<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\Handlers;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\Vehicle\StartVehicleMutationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\VehicleMutationRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\CatalogMutationContractMismatchReporter;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\Validators\VehicleMutationPayloadValidator;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Принимает RabbitMQ-сообщение мутации автомобилей и запускает сценарий.
 */
final readonly class VehicleMutationRequestedHandler
{
    /**
     * Инициализирует зависимости класса через контейнер.
     */
    public function __construct(
        private StartVehicleMutationUseCaseInterface $useCase,
        private VehicleMutationPayloadValidator $validator,
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
            Log::error('RabbitMQ: Vehicle mutation payload validation failed', [
                'invalid_keys' => $invalidKeys,
            ]);
            $this->contractMismatchReporter->report(CatalogEntityEnum::Vehicle, $data, $invalidKeys);

            return;
        }

        $payload = $validator->validated();

        try {
            $requestDto = VehicleMutationRequestDTO::fromArray($payload);
        } catch (Throwable $e) {
            Log::error('RabbitMQ: Vehicle mutation payload contract mismatch', [
                'operation_id' => $payload['operation_id'] ?? null,
                'exception' => $e,
            ]);
            $this->contractMismatchReporter->report(CatalogEntityEnum::Vehicle, $payload, ['payload']);

            return;
        }

        $this->useCase->execute($requestDto);
    }
}
