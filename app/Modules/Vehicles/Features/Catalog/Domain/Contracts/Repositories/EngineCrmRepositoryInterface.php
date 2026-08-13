<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\Crm\EngineCrmListItemDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\Crm\EngineCrmPageDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\EngineCrmReadQueryDTO;

/**
 * Описывает read port двигателей для CRM API.
 */
interface EngineCrmRepositoryInterface
{
    public function paginate(EngineCrmReadQueryDTO $query): EngineCrmPageDTO;

    public function findById(int $id): ?EngineCrmListItemDTO;
}
