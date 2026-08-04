<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\Handlers;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Factories\ManufacturerMutationRequestFactoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\Manufacturer\StartManufacturerMutationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\Validators\ManufacturerMutationPayloadValidator;
use Illuminate\Support\Facades\Log;
use PkmStudio\DanWireContracts\Vehicles\Modules\Vehicles\Features\Catalog\Mutation\DTO\ManufacturerMutationRequested;

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
        private ManufacturerMutationRequestFactoryInterface $factory,
        private ManufacturerMutationPayloadValidator $validator,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): void
    {
        $validator = $this->validator->make($data);
        $validationFailed = $validator->fails();

        if ($validationFailed) {
            Log::error('RabbitMQ: Manufacturer mutation payload validation failed', [
                'invalid_keys' => array_keys($validator->errors()->toArray()),
            ]);

            return;
        }

        $payload = ManufacturerMutationRequested::fromArray($validator->validated())->toArray();
        $request = $this->factory->make($payload);
        $this->useCase->execute($request);
    }
}
