<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\Handlers;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Factories\EngineMutationRequestFactoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\Engine\StartEngineMutationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\Validators\EngineMutationPayloadValidator;
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
        $validationFailed = $validator->fails();

        if ($validationFailed) {
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
