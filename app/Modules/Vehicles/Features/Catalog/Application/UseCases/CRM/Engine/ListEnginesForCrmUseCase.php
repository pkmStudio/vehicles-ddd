<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\CRM\Engine;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\EngineCrmRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\CRM\Engine\ListEnginesForCrmUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\Crm\EngineCrmPageDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\EngineCrmReadQueryDTO;

/**
 * Оркестрирует CRM read-сценарий списка двигателей.
 */
final readonly class ListEnginesForCrmUseCase implements ListEnginesForCrmUseCaseInterface
{
    public function __construct(
        private EngineCrmRepositoryInterface $engines,
    ) {}

    public function execute(EngineCrmReadQueryDTO $query): EngineCrmPageDTO
    {
        return $this->engines->paginate($query);
    }
}
