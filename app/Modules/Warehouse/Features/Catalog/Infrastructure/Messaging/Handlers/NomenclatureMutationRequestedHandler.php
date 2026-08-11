<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging\Handlers;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Nomenclature\Mutations\StartNomenclatureMutationUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\NomenclatureMutationRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogEntityEnum;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging\Validators\NomenclatureMutationPayloadValidator;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging\WarehouseCatalogMutationContractMismatchReporter;
use Illuminate\Support\Facades\Log;
use Throwable;

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
        private NomenclatureMutationPayloadValidator $validator,
        private WarehouseCatalogMutationContractMismatchReporter $contractMismatchReporter,
    ) {}

    /**
     * Валидирует payload, собирает DTO и запускает сценарий мутации номенклатуры.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): void
    {
        $validator = $this->validator->make($data);
        $validationFailed = $validator->fails();

        if ($validationFailed) {
            $invalidKeys = array_keys($validator->errors()->toArray());
            Log::error(
                message: 'RabbitMQ: Warehouse nomenclature mutation payload validation failed',
                context: [
                    'invalid_keys' => $invalidKeys,
                ],
            );
            $this->contractMismatchReporter->report(WarehouseCatalogEntityEnum::Nomenclature, $data, $invalidKeys);

            return;
        }

        $payload = $validator->validated();

        try {
            $requestDto = NomenclatureMutationRequestDTO::fromArray($payload);
        } catch (Throwable $e) {
            Log::error('RabbitMQ: Warehouse nomenclature mutation payload contract mismatch', [
                'operation_id' => $payload['operation_id'] ?? null,
                'exception' => $e,
            ]);
            $this->contractMismatchReporter->report(WarehouseCatalogEntityEnum::Nomenclature, $payload, ['payload']);

            return;
        }

        $this->useCase->execute($requestDto);
    }
}
