<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Contracts\UseCases\PartSpecification;

use App\Vehicles\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Vehicles\Catalog\Domain\DTOs\PartSpecification\CreatePartSpecificationRequestDTO;

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
