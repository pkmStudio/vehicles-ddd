<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Nomenclature\Crm;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmOptionDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmPageDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\NomenclatureCrmReadQueryDTO;
use Illuminate\Support\Collection;

interface ListNomenclaturesForCrmUseCaseInterface
{
    /**
     * Возвращает страницу данных для CRM-чтения.
     *
     * Шаги:
     * 1) Получить DTO параметров CRM-чтения.
     * 2) Передать фильтры, сортировку и пагинацию в репозиторий.
     * 3) Вернуть готовую страницу DTO без дополнительного преобразования.
     */
    public function execute(NomenclatureCrmReadQueryDTO $query): NomenclatureCrmPageDTO;

    /**
     * @return Collection<int, NomenclatureCrmOptionDTO>
     *
     * Шаги:
     * 1) Принять поисковую строку, выбранный id и лимит options.
     * 2) Запросить варианты типов в репозиторий.
     * 3) Вернуть коллекцию DTO для CRM-select.
     */
    public function types(?string $query = null, ?int $id = null, int $limit = 50): Collection;

    /**
     * @return Collection<int, NomenclatureCrmOptionDTO>
     *
     * Шаги:
     * 1) Принять поисковую строку, выбранный id и лимит options.
     * 2) Запросить варианты брендов в репозиторий.
     * 3) Вернуть коллекцию DTO для CRM-select.
     */
    public function brands(?string $query = null, ?int $id = null, int $limit = 50): Collection;
}
