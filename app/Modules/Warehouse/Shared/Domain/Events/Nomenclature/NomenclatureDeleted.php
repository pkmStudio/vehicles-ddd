<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Shared\Domain\Events\Nomenclature;

use App\Modules\Warehouse\Shared\Domain\DTOs\Events\NomenclatureIntegrationDeletionContextDTO;
use Illuminate\Support\Collection;

/**
 * Доменный факт удаления Warehouse-номенклатуры.
 */
final readonly class NomenclatureDeleted
{
    public int $userId;

    public string $operationId;

    public int $nomenclatureId;

    public string $partNumber;

    /** @var Collection<int, NomenclatureIntegrationDeletionContextDTO> */
    public Collection $integrations;

    /**
     * @param  array<int, NomenclatureIntegrationDeletionContextDTO>  $integrations
     */
    public function __construct(
        int $userId,
        string $operationId,
        int $nomenclatureId,
        string $partNumber,
        array $integrations = [],
    ) {
        $this->userId = $userId;
        $this->operationId = $operationId;
        $this->nomenclatureId = $nomenclatureId;
        $this->partNumber = $partNumber;
        $this->integrations = new Collection($integrations);
    }
}
