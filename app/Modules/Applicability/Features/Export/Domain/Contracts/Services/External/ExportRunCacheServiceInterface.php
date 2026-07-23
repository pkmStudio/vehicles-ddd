<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Domain\Contracts\Services\External;

interface ExportRunCacheServiceInterface
{
    public function accept(string $runId): bool;

    public function forgetAccepted(string $runId): void;
}
