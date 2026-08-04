<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging\Handlers;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Factories\KitMutationRequestFactoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Kit\StartKitMutationUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging\Validators\KitMutationPayloadValidator;
use Illuminate\Support\Facades\Log;
use PkmStudio\DanWireContracts\Vehicles\Modules\Warehouse\Features\Catalog\Mutation\DTO\KitMutationRequested;

/**
 * Принимает RabbitMQ-сообщение мутации Warehouse-наборов и запускает сценарий.
 */
final readonly class KitMutationRequestedHandler
{
    /**
     * Инициализирует use case, factory и validator.
     */
    public function __construct(
        private StartKitMutationUseCaseInterface $useCase,
        private KitMutationRequestFactoryInterface $factory,
        private KitMutationPayloadValidator $validator,
    ) {}

    /**
     * Валидирует payload, собирает DTO и запускает сценарий мутации набора.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): void
    {
        $validator = $this->validator->make($data);
        $validationFailed = $validator->fails();

        if ($validationFailed) {
            Log::error(
                message: 'RabbitMQ: Warehouse kit mutation payload validation failed',
                context: [
                    'invalid_keys' => array_keys($validator->errors()->toArray()),
                ],
            );

            return;
        }

        $payload = KitMutationRequested::fromArray($validator->validated())->toArray();
        $requestDto = $this->factory->make($payload);
        $this->useCase->execute($requestDto);
    }
}
