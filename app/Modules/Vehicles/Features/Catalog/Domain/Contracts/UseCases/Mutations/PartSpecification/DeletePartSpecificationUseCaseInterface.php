<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\PartSpecification;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\PartSpecification\DeletePartSpecificationRequestDTO;

/**
 * Описывает порт сценария удаления спецификаций деталей из внешнего сообщения.
 */
interface DeletePartSpecificationUseCaseInterface
{
    /**
     * Выполняет сценарий удаления спеки.
     *
     * Шаги:
     * 1) Принять delete request с id specification.
     * 2) Проверить idempotency и существование записи.
     * 3) Удалить specification, опубликовать domain event и mutation result.
     */
    public function execute(DeletePartSpecificationRequestDTO $request): ?CatalogMutationResultDTO;
}
