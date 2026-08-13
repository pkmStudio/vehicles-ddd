<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Infrastructure\Messaging\Handlers;

use App\Modules\Applicability\Features\Calculation\Infrastructure\Jobs\DispatchKitApplicabilityCalculationJob;
use App\Modules\Applicability\Features\Calculation\Infrastructure\Messaging\Validators\CalculationRequestedPayloadValidator;
use Illuminate\Support\Facades\Log;

final readonly class CalculationRequestedHandler
{
    /**
     * Получает validator входящего RabbitMQ payload.
     *
     * Шаги:
     * 1. Сохраняет validator request-сообщения.
     * 2. Оставляет проверку и dispatch job методу `handle()`.
     */
    public function __construct(
        private CalculationRequestedPayloadValidator $validator,
    ) {}

    /**
     * Валидирует внешний запрос расчета и ставит job в очередь.
     *
     * Шаги:
     * 1. Строит validator для входящего payload.
     * 2. Логирует invalid keys и завершает обработку при ошибках валидации.
     * 3. Берет validated data и нормализует scalar values.
     * 4. Dispatch-ит `CalculateKitApplicabilityJob` с operation/user context.
     *
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

        DispatchKitApplicabilityCalculationJob::dispatch(
            kitId: isset($data['kit_id']) ? (int) $data['kit_id'] : null,
            chunk: (int) ($data['chunk'] ?? 1000),
            operationId: (string) $data['operation_id'],
            userId: (int) $data['user_id'],
        );
    }
}
