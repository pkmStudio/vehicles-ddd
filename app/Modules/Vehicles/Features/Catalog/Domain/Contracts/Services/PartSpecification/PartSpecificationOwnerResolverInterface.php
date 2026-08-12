<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\PartSpecification;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\PartSpecification\PartSpecificationOwnerDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\PartSpecification\PartSpecificationOwnerResolutionDTO;

/**
 * Описывает общий resolver владельца PartSpecification.
 */
interface PartSpecificationOwnerResolverInterface
{
    /**
     * Разрешает владельца спеки во внутренний id записи.
     *
     * Шаги:
     * 1) Найти owner record по внешнему id и partable type.
     * 2) Создать или обновить owner из payload, если конкретная реализация это поддерживает.
     * 3) Вернуть resolved owner или reject reason.
     */
    public function execute(PartSpecificationOwnerDTO $owner): PartSpecificationOwnerResolutionDTO;
}
