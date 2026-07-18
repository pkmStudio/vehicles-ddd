<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Domain\ModelData;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Снимок integration-state между Warehouse-номенклатурой и товаром МойСклад.
 */
#[MapName(SnakeCaseMapper::class)]
final class NomenclatureIntegrationData extends Data
{
    /**
     * Хранит состояние синхронизации с внешним товаром.
     */
    public function __construct(
        public readonly int $id,
        public readonly string $provider,
        public readonly string $syncStatus,
        public readonly ?int $nomenclatureId = null,
        public readonly ?string $externalId = null,
        public readonly ?string $externalCode = null,
        public readonly ?string $payloadHash = null,
        public readonly ?string $lastError = null,
    ) {}
}
