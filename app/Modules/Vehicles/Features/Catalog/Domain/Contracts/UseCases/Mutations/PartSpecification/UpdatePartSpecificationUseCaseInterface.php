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
     *
     * Шаги:
     * 1) Принять update request с id, owner, template и details.
     * 2) Проверить idempotency, наличие записи, details policy и owner resolution.
     * 3) Обновить specification, опубликовать domain event и mutation result.
     */
    public function execute(UpdatePartSpecificationRequestDTO $request): ?CatalogMutationResultDTO;
}
