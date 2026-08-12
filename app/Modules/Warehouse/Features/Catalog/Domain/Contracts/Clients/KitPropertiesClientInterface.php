<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Clients;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\KitProperties\KitPropertiesDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\NomenclatureData;

/**
 * Описывает клиент границу сборки свойств комплекта.
 */
interface KitPropertiesClientInterface
{
    /**
     * Собирает свойства комплекта из номенклатур.
     *
     * Шаги:
     * 1. Принять набор `NomenclatureData`, выбранных для комплекта.
     * 2. Вернуть DTO свойств комплекта или ошибку клиент границу.
     *
     * @param  array<int, NomenclatureData>  $nomenclatures
     */
    public function build(array $nomenclatures): KitPropertiesDTO;
}
