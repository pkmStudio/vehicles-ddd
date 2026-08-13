<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\Contracts\Clients;

use App\Modules\Warehouse\Features\Import\Domain\DTOs\KitProperties\KitPropertiesDTO;
use App\Modules\Warehouse\Features\Import\Domain\ModelData\NomenclatureData;

interface KitPropertiesClientInterface
{
    /**
     * Собирает свойства набора по номенклатурам из строки импорта.
     *
     * Шаги:
     * 1) Принять упорядоченный список номенклатур набора.
     * 2) Передать их во внешний контекст свойств наборов через adapter.
     * 3) Вернуть DTO с calculated properties и хешем состава.
     *
     * @param  array<int, NomenclatureData>  $nomenclatures
     */
    public function build(array $nomenclatures): KitPropertiesDTO;
}
