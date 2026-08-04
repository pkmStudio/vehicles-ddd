<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Domain\DTOs\WiperAdapterAudit;

final readonly class WiperAdapterAuditExportRowDTO
{
    public function __construct(
        public int $kitId,
        public string $kit,
        public string $mismatchedAdapters,
        public string $place,
    ) {}
}
