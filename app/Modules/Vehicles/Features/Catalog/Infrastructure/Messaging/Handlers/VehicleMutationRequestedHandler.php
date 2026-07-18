<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\Handlers;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Factories\VehicleMutationRequestFactoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Vehicle\StartVehicleMutationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\Validators\VehicleMutationPayloadValidator;
use Illuminate\Support\Facades\Log;

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
        private VehicleMutationRequestFactoryInterface $factory,
        private VehicleMutationPayloadValidator $validator,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): void
    {
        $validator = $this->validator->make($data);

        if ($validator->fails()) {
            Log::error('RabbitMQ: Vehicle mutation payload validation failed', [
                'invalid_keys' => array_keys($validator->errors()->toArray()),
            ]);

            return;
        }

        $data = $validator->validated();
        $requestDto = $this->factory->make($data);
        $this->useCase->execute($requestDto);
    }
}
