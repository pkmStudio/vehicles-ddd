<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature;

/**
 * Описывает одну ошибку строки при массовом удалении номенклатуры.
 */
final readonly class NomenclatureBulkDeleteErrorDTO
{
    /**
     * Получает id записи, machine-readable причину и опциональный бизнес-ключ.
     */
    public function __construct(
        public ?int $id,
        public string $reason,
        public ?string $businessKey = null,
    ) {}
}
