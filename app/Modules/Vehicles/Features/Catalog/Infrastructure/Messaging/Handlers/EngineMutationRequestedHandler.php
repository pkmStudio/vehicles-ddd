<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\Handlers;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\Engine\StartEngineMutationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\EngineMutationRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\Validators\EngineMutationPayloadValidator;
use Illuminate\Support\Facades\Log;
use PkmStudio\DanWireContracts\Vehicles\Modules\Vehicles\Features\Catalog\Mutation\DTO\EngineMutationRequested;

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

        $payload = EngineMutationRequested::fromArray($validator->validated())->toArray();
        $request = EngineMutationRequestDTO::fromArray($payload);
        $this->useCase->execute($request);
    }
}
