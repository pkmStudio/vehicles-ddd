<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Infrastructure\Messaging\Handlers;

use App\Warehouse\Catalog\Domain\Contracts\Factories\NomenclatureMutationRequestFactoryInterface;
use App\Warehouse\Catalog\Domain\Contracts\UseCases\Nomenclature\StartNomenclatureMutationUseCaseInterface;
use App\Warehouse\Catalog\Infrastructure\Messaging\Validators\NomenclatureMutationPayloadValidator;
use Illuminate\Support\Facades\Log;

/**
 * Принимает RabbitMQ-сообщение мутации Warehouse-номенклатуры и запускает сценарий.
 */
final readonly class NomenclatureMutationRequestedHandler
{
    /**
     * Инициализирует use case, factory и validator.
     */
    public function __construct(
        private StartNomenclatureMutationUseCaseInterface $useCase,
        private NomenclatureMutationRequestFactoryInterface $factory,
        private NomenclatureMutationPayloadValidator $validator,
    ) {}

    /**
     * Валидирует payload, собирает DTO и запускает сценарий мутации номенклатуры.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): void
    {
        $validator = $this->validator->make($data);

        if ($validator->fails()) {
            Log::error('RabbitMQ: Warehouse nomenclature mutation payload validation failed', [
                'invalid_keys' => array_keys($validator->errors()->toArray()),
            ]);

            return;
        }

        $payload = $validator->validated();
        $requestDto = $this->factory->make($payload);
        $this->useCase->execute($requestDto);
    }
}
