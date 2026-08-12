<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Nomenclature\Crm;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmListItemDTO;

interface ShowNomenclatureForCrmUseCaseInterface
{
    /**
     * Возвращает детальную CRM-проекцию по идентификатору.
     *
     * Шаги:
     * 1) Принять идентификатор записи каталога.
     * 2) Запросить детальную CRM-проекцию в репозиторий.
     * 3) Вернуть DTO или null, если запись не найдена.
     */
    public function execute(int $id): ?NomenclatureCrmListItemDTO;
}
