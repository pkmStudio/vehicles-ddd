<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Catalog\Modification;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Catalog\CatalogModificationContextDTO;

/**
 * Use case port REST-detail модификации публичного каталога.
 */
interface ShowModificationForCatalogUseCaseInterface
{
    /**
     * Возвращает detail-контекст модификации публичного каталога.
     *
     * Шаги:
     * 1) Найти модификацию по catalog id.
     * 2) Проверить связанный автомобиль и производителя.
     * 3) Вернуть context DTO или null, если цепочка недоступна.
     */
    public function execute(int $modificationId): ?CatalogModificationContextDTO;
}
