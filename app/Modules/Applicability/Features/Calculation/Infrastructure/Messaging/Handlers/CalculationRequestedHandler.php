<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Infrastructure\Messaging\Handlers;

use App\Modules\Applicability\Features\Calculation\Infrastructure\Jobs\CalculateKitApplicabilityJob;
use App\Modules\Applicability\Features\Calculation\Infrastructure\Messaging\Validators\CalculationRequestedPayloadValidator;
use Illuminate\Support\Facades\Log;

final readonly class CalculationRequestedHandler
{
    public function __construct(
        private CalculationRequestedPayloadValidator $validator,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): void
    {
        $validator = $this->validator->make($data);

        if ($validator->fails()) {
            Log::error('RabbitMQ: Applicability calculation request payload validation failed', [
                'invalid_keys' => array_keys($validator->errors()->toArray()),
            ]);

            return;
        }

        $data = $validator->validated();

        CalculateKitApplicabilityJob::dispatch(
            kitId: isset($data['kit_id']) ? (int) $data['kit_id'] : null,
            chunk: (int) ($data['chunk'] ?? 1000),
            operationId: (string) $data['operation_id'],
            userId: (int) $data['user_id'],
        );
    }
}
