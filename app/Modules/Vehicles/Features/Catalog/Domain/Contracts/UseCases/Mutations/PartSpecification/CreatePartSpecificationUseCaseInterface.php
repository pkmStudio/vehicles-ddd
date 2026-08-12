<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\PartSpecification;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\PartSpecification\CreatePartSpecificationRequestDTO;

/**
 * Описывает порт сценария создания спецификаций деталей из внешнего сообщения.
 */
interface CreatePartSpecificationUseCaseInterface
{
    /**
     * Выполняет сценарий создания спеки.
     *
     * Шаги:
     * 1) Принять create request с owner, template и details.
     * 2) Проверить idempotency, duplicate id, details policy и owner resolution.
     * 3) Создать specification, опубликовать domain event и mutation result.
     */
    public function execute(CreatePartSpecificationRequestDTO $request): ?CatalogMutationResultDTO;
}
