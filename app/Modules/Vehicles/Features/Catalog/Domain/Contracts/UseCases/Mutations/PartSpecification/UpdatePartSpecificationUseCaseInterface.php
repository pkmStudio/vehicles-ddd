<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\PartSpecification;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\PartSpecification\UpdatePartSpecificationRequestDTO;

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
