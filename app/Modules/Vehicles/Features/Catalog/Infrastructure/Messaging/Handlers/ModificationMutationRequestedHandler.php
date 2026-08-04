<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\Handlers;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\Modification\StartModificationMutationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Modification\ModificationMutationRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\Validators\ModificationMutationPayloadValidator;
use Illuminate\Support\Facades\Log;
use PkmStudio\DanWireContracts\Vehicles\Modules\Vehicles\Features\Catalog\Mutation\DTO\ModificationMutationRequested;

/**
 * Принимает RabbitMQ-сообщение мутации модификаций и запускает сценарий.
 */
final readonly class ModificationMutationRequestedHandler
{
    /**
     * Инициализирует зависимости класса через контейнер.
     */
    public function __construct(
        private StartModificationMutationUseCaseInterface $useCase,
        private ModificationMutationPayloadValidator $validator,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): void
    {
        $validator = $this->validator->make($data);
        $validationFailed = $validator->fails();

        if ($validationFailed) {
            Log::error('RabbitMQ: Modification mutation payload validation failed', [
                'invalid_keys' => array_keys($validator->errors()->toArray()),
            ]);

            return;
        }

        $payload = ModificationMutationRequested::fromArray($validator->validated())->toArray();
        $request = ModificationMutationRequestDTO::fromArray($payload);
        $this->useCase->execute($request);
    }
}
