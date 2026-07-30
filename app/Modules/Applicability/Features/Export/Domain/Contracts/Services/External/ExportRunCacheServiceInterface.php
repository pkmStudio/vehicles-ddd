<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Domain\Contracts\Services\External;

interface ExportRunCacheServiceInterface
{
    public function accept(string $operationId): bool;

    public function forgetAccepted(string $operationId): void;
}
