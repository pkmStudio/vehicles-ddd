<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Infrastructure\Jobs;

use App\Modules\Applicability\Features\Calculation\Application\UseCases\CalculateKitApplicabilityUseCase;
use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\ExternalCalculationContextServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable as FoundationQueueable;

final class CalculateKitApplicabilityJob implements ShouldQueue
{
    use FoundationQueueable;

    /**
     * Создает queued job пересчета применяемости.
     *
     * Шаги:
     * 1. Сохраняет optional kit id для точечного расчета.
     * 2. Сохраняет chunk size для чтения Warehouse kits.
     * 3. Сохраняет внешний operation id и user id для callback-контекста.
     */
    public function __construct(
        private readonly ?int $kitId = null,
        private readonly int $chunk = 1000,
        private readonly ?string $operationId = null,
        private readonly ?int $userId = null,
    ) {}

    /**
     * Выполняет отложенный расчет применяемости.
     *
     * Шаги:
     * 1. Если передан внешний контекст, сохраняет user id по operation id.
     * 2. Вызывает use case пересчета с kit filter, chunk size и operation id.
     * 3. Оставляет публикацию итогового факта самому use case.
     */
    public function handle(
        CalculateKitApplicabilityUseCase $useCase,
        ExternalCalculationContextServiceInterface $context,
    ): void {
        if ($this->operationId !== null && $this->userId !== null) {
            $context->rememberUserId($this->operationId, $this->userId);
        }

        $useCase->execute(
            kitId: $this->kitId,
            chunk: $this->chunk,
            operationId: $this->operationId,
        );
    }
}
