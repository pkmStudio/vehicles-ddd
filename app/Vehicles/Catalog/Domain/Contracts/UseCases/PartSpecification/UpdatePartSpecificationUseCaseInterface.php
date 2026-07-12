<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Contracts\UseCases\PartSpecification;

use App\Vehicles\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Vehicles\Catalog\Domain\DTOs\PartSpecification\UpdatePartSpecificationRequestDTO;

/**
 * Описывает порт сценария обновления спецификаций деталей из внешнего сообщения.
 */
interface UpdatePartSpecificationUseCaseInterface
{
    /**
     * Выполняет сценарий обновления спеки.
     */
    public function execute(UpdatePartSpecificationRequestDTO $request): ?CatalogMutationResultDTO;
}
