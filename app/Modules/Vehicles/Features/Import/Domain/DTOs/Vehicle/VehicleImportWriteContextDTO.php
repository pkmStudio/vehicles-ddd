<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\DTOs\Vehicle;

use App\Modules\Vehicles\Features\Import\Domain\Enums\VehicleImportSourceEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;

/**
 * Передает source ownership контекст для import write policy.
 */
final readonly class VehicleImportWriteContextDTO
{
    /**
     * Инициализирует immutable-контекст строки import workflow.
     */
    public function __construct(
        public VehicleImportSourceEnum $source,
        public ProviderEnum $sourceProvider,
        public string $operationId,
        public int $msId,
        public ?string $rowIdentifier = null,
    ) {}
}
