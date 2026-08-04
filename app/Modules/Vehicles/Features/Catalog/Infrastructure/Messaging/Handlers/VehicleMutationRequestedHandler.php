<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\Handlers;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\Vehicle\StartVehicleMutationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\VehicleMutationRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\Validators\VehicleMutationPayloadValidator;
use Illuminate\Support\Facades\Log;
use PkmStudio\DanWireContracts\Vehicles\Modules\Vehicles\Features\Catalog\Mutation\DTO\VehicleMutationRequested;

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
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): void
    {
        $validator = $this->validator->make($data);
        $validationFailed = $validator->fails();

        if ($validationFailed) {
            Log::error('RabbitMQ: Vehicle mutation payload validation failed', [
                'invalid_keys' => array_keys($validator->errors()->toArray()),
            ]);

            return;
        }

        $payload = VehicleMutationRequested::fromArray($validator->validated())->toArray();
        $requestDto = VehicleMutationRequestDTO::fromArray($payload);
        $this->useCase->execute($requestDto);
    }
}
