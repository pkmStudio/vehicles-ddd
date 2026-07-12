<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Infrastructure\Messaging\Handlers;

use App\Vehicles\Catalog\Domain\Contracts\Factories\EngineMutationRequestFactoryInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Engine\StartEngineMutationUseCaseInterface;
use App\Vehicles\Catalog\Infrastructure\Messaging\Validators\EngineMutationPayloadValidator;
use Illuminate\Support\Facades\Log;

/**
 * Принимает RabbitMQ-сообщение мутации двигателей и запускает сценарий.
 */
final readonly class EngineMutationRequestedHandler
{
    /**
     * Инициализирует зависимости класса через контейнер.
     */
    public function __construct(
        private StartEngineMutationUseCaseInterface $useCase,
        private EngineMutationRequestFactoryInterface $factory,
        private EngineMutationPayloadValidator $validator,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): void
    {
        $validator = $this->validator->make($data);

        if ($validator->fails()) {
            Log::error('RabbitMQ: Engine mutation payload validation failed', [
                'invalid_keys' => array_keys($validator->errors()->toArray()),
            ]);

            return;
        }

        $payload = $validator->validated();
        $request = $this->factory->make($payload);
        $this->useCase->execute($request);
    }
}
