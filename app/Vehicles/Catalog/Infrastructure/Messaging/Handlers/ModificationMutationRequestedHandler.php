<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Infrastructure\Messaging\Handlers;

use App\Vehicles\Catalog\Domain\Contracts\Factories\ModificationMutationRequestFactoryInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Modification\StartModificationMutationUseCaseInterface;
use App\Vehicles\Catalog\Infrastructure\Messaging\Validators\ModificationMutationPayloadValidator;
use Illuminate\Support\Facades\Log;

final readonly class ModificationMutationRequestedHandler
{
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

        if ($validator->fails()) {
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
