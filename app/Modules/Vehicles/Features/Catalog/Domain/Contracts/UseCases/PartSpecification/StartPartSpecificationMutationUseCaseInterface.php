<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\PartSpecification;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\PartSpecification\PartSpecificationMutationRequestDTO;

/**
 * Описывает порт сценария мутации спецификаций деталей из внешнего сообщения.
 */
interface StartPartSpecificationMutationUseCaseInterface
{
    /**
     * Запускает сценарий мутации спеки.
     */
    public function execute(PartSpecificationMutationRequestDTO $request): ?CatalogMutationResultDTO;
}
