<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\Handlers;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\Modification\StartModificationMutationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Modification\ModificationMutationRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\CatalogMutationContractMismatchReporter;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\Validators\ModificationMutationPayloadValidator;
use Illuminate\Support\Facades\Log;
use Throwable;

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
        private CatalogMutationContractMismatchReporter $contractMismatchReporter,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): void
    {
        $validator = $this->validator->make($data);
        $validationFailed = $validator->fails();

        if ($validationFailed) {
            $invalidKeys = array_keys($validator->errors()->toArray());
            Log::error('RabbitMQ: Modification mutation payload validation failed', [
                'invalid_keys' => $invalidKeys,
            ]);
            $this->contractMismatchReporter->report(CatalogEntityEnum::Modification, $data, $invalidKeys);

            return;
        }

        $payload = $validator->validated();

        try {
            $request = ModificationMutationRequestDTO::fromArray($payload);
        } catch (Throwable $e) {
            Log::error('RabbitMQ: Modification mutation payload contract mismatch', [
                'operation_id' => $payload['operation_id'] ?? null,
                'exception' => $e,
            ]);
            $this->contractMismatchReporter->report(CatalogEntityEnum::Modification, $payload, ['payload']);

            return;
        }

        $this->useCase->execute($request);
    }
}
