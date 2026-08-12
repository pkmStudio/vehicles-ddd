<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\PartSpecification;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\PartSpecification\PartSpecificationMutationRequestDTO;

/**
 * Описывает порт сценария мутации спецификаций деталей из внешнего сообщения.
 */
interface StartPartSpecificationMutationUseCaseInterface
{
    /**
     * Запускает сценарий мутации спеки.
     *
     * Шаги:
     * 1) Прочитать operation из общего mutation request.
     * 2) Выбрать create/update/delete branch.
     * 3) Делегировать typed request профильному use case.
     */
    public function execute(PartSpecificationMutationRequestDTO $request): ?CatalogMutationResultDTO;
}
