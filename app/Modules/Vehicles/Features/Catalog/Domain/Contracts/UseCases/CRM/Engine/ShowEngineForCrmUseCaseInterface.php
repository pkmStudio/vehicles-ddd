<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\CRM\Engine;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\Crm\EngineCrmListItemDTO;

interface ShowEngineForCrmUseCaseInterface
{
    public function execute(int $id): ?EngineCrmListItemDTO;
}
