<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Clients;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\Crm\EngineCrmListItemDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\Crm\EngineCrmPageDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\EngineCrmReadQueryDTO;

/**
 * Описывает read-only клиент CRM сценариев двигателей.
 */
interface EngineCrmClientInterface
{
    public function paginate(EngineCrmReadQueryDTO $query): EngineCrmPageDTO;

    public function show(int $id): ?EngineCrmListItemDTO;
}
