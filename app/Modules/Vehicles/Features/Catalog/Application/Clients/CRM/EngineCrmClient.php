<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\Clients\CRM;

use App\Modules\Vehicles\Features\Catalog\Application\UseCases\CRM\Engine\ListEnginesForCrmUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\CRM\Engine\ShowEngineForCrmUseCase;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Clients\EngineCrmClientInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\Crm\EngineCrmListItemDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\Crm\EngineCrmPageDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\EngineCrmReadQueryDTO;

/**
 * Read-only клиент CRM сценариев двигателей.
 */
final readonly class EngineCrmClient implements EngineCrmClientInterface
{
    public function __construct(
        private ListEnginesForCrmUseCase $list,
        private ShowEngineForCrmUseCase $show,
    ) {}

    public function paginate(EngineCrmReadQueryDTO $query): EngineCrmPageDTO
    {
        return $this->list->execute($query);
    }

    public function show(int $id): ?EngineCrmListItemDTO
    {
        return $this->show->execute($id);
    }
}
