<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Contracts\UseCases\PartSpecification;

use App\Vehicles\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Vehicles\Catalog\Domain\DTOs\PartSpecification\DeletePartSpecificationRequestDTO;

/**
 * Описывает порт сценария удаления спецификаций деталей из внешнего сообщения.
 */
interface DeletePartSpecificationUseCaseInterface
{
    /**
     * Выполняет сценарий удаления спеки.
     */
    public function execute(DeletePartSpecificationRequestDTO $request): ?CatalogMutationResultDTO;
}
