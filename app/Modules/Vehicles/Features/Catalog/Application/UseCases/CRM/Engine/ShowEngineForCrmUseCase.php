<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\CRM\Engine;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\EngineCrmRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\Crm\EngineCrmListItemDTO;

/**
 * Оркестрирует CRM read-сценарий detail двигателя.
 */
final readonly class ShowEngineForCrmUseCase
{
    public function __construct(
        private EngineCrmRepositoryInterface $engines,
    ) {}

    public function execute(int $id): ?EngineCrmListItemDTO
    {
        return $this->engines->findById($id);
    }
}
