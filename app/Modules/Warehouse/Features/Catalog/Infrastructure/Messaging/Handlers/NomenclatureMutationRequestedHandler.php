<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging\Handlers;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Nomenclature\Mutations\StartNomenclatureMutationUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\NomenclatureMutationRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging\Validators\NomenclatureMutationPayloadValidator;
use Illuminate\Support\Facades\Log;
use PkmStudio\DanWireContracts\Vehicles\Modules\Warehouse\Features\Catalog\Mutation\DTO\NomenclatureMutationRequested;

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
            Log::error(
                message: 'RabbitMQ: Warehouse nomenclature mutation payload validation failed',
                context: [
                    'invalid_keys' => array_keys($validator->errors()->toArray()),
                ],
            );

            return;
        }

        $payload = NomenclatureMutationRequested::fromArray($validator->validated())->toArray();
        $requestDto = NomenclatureMutationRequestDTO::fromArray($payload);
        $this->useCase->execute($requestDto);
    }
}
