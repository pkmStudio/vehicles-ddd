<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Shared\Domain\Events\Nomenclature;

use App\Modules\Warehouse\Shared\Domain\DTOs\Events\NomenclatureEventPayloadDTO;

/**
 * Доменный факт создания Warehouse-номенклатуры.
 */
final readonly class NomenclatureCreated
{
    public function __construct(
        public int $userId,
        public string $operationId,
        public NomenclatureEventPayloadDTO $nomenclature,
    ) {}
}
