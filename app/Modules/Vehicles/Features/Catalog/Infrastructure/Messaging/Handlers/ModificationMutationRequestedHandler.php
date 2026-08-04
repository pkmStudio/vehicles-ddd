<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\Handlers;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Factories\ModificationMutationRequestFactoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\Modification\StartModificationMutationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\Validators\ModificationMutationPayloadValidator;
use Illuminate\Support\Facades\Log;

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
        private ModificationMutationRequestFactoryInterface $factory,
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

        $payload = $validator->validated();
        $request = $this->factory->make($payload);
        $this->useCase->execute($request);
    }
}
