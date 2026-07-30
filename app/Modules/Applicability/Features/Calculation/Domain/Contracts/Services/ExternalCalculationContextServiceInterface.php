<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services;

interface ExternalCalculationContextServiceInterface
{
    public function rememberUserId(string $operationId, int $userId): void;

    public function pullUserId(string $operationId): ?int;
}
