<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\PartSpecification;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\PartSpecification\CreatePartSpecificationRequestDTO;

/**
 * Описывает порт сценария создания спецификаций деталей из внешнего сообщения.
 */
interface CreatePartSpecificationUseCaseInterface
{
    /**
     * Выполняет сценарий создания спеки.
     */
    public function execute(CreatePartSpecificationRequestDTO $request): ?CatalogMutationResultDTO;
}
