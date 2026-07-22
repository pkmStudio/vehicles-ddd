<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging\Handlers;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Factories\PackDimensionMutationRequestFactoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\PackDimension\StartPackDimensionMutationUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging\Validators\PackDimensionMutationPayloadValidator;
use Illuminate\Support\Facades\Log;

/**
 * Принимает RabbitMQ-сообщение мутации упаковочных размеров Warehouse и запускает сценарий.
 */
final readonly class PackDimensionMutationRequestedHandler
{
    /**
     * Инициализирует use case, factory и validator.
     */
    public function __construct(
        private StartPackDimensionMutationUseCaseInterface $useCase,
        private PackDimensionMutationRequestFactoryInterface $factory,
        private PackDimensionMutationPayloadValidator $validator,
    ) {}

    /**
     * Валидирует payload, собирает DTO и запускает сценарий мутации упаковочного размера.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): void
    {
        $validator = $this->validator->make($data);
        $validationFailed = $validator->fails();

        if ($validationFailed) {
            Log::error(
                message: 'RabbitMQ: Warehouse pack dimension mutation payload validation failed',
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
