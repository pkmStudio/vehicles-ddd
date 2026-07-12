<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Infrastructure\Messaging\Handlers;

use App\Vehicles\Catalog\Domain\Contracts\Factories\ManufacturerMutationRequestFactoryInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Manufacturer\StartManufacturerMutationUseCaseInterface;
use App\Vehicles\Catalog\Infrastructure\Messaging\Validators\ManufacturerMutationPayloadValidator;
use Illuminate\Support\Facades\Log;

final readonly class ManufacturerMutationRequestedHandler
{
    public function __construct(
        private StartManufacturerMutationUseCaseInterface $useCase,
        private ManufacturerMutationRequestFactoryInterface $factory,
        private ManufacturerMutationPayloadValidator $validator,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): void
    {
        $validator = $this->validator->make($data);

        if ($validator->fails()) {
            Log::error('RabbitMQ: Manufacturer mutation payload validation failed', [
                'invalid_keys' => array_keys($validator->errors()->toArray()),
            ]);

            return;
        }

        $payload = $validator->validated();
        $request = $this->factory->make($payload);
        $this->useCase->execute($request);
    }
}
