<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging\Handlers;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Factories\BrandMutationRequestFactoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Brand\StartBrandMutationUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging\Validators\BrandMutationPayloadValidator;
use Illuminate\Support\Facades\Log;

/**
 * Принимает RabbitMQ-сообщение мутации Warehouse-брендов и запускает сценарий.
 */
final readonly class BrandMutationRequestedHandler
{
    /**
     * Инициализирует use case, factory и validator.
     */
    public function __construct(
        private StartBrandMutationUseCaseInterface $useCase,
        private BrandMutationRequestFactoryInterface $factory,
        private BrandMutationPayloadValidator $validator,
    ) {}

    /**
     * Валидирует payload, собирает DTO и запускает сценарий мутации бренда.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): void
    {
        $validator = $this->validator->make($data);
        $validationFailed = $validator->fails();

        if ($validationFailed) {
            Log::error(
                message: 'RabbitMQ: Warehouse brand mutation payload validation failed',
                context: [
                    'invalid_keys' => array_keys($validator->errors()->toArray()),
                ],
            );

            return;
        }

        $payload = $validator->validated();
        $requestDto = $this->factory->make($payload);
        $this->useCase->execute($requestDto);
    }
}
