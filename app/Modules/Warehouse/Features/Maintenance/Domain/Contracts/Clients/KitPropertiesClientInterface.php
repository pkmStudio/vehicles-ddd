<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Maintenance\Domain\Contracts\Clients;

use App\Modules\Warehouse\Features\Maintenance\Domain\DTOs\KitProperties\KitPropertiesDTO;
use App\Modules\Warehouse\Features\Maintenance\Infrastructure\Models\Nomenclature;

interface KitPropertiesClientInterface
{
    /**
     * Рассчитывает свойства набора через локальный client boundary к KitProperties.
     * Шаги:
     * 1) Принять Maintenance-модели номенклатур с загруженным type.
     * 2) Передать состав в adapter, который переведёт модели в DTO KitProperties.
     * 3) Вернуть локальный DTO с рассчитанными полями для обновления Kit.
     *
     * @param  array<int, Nomenclature>  $nomenclatures
     */
    public function build(array $nomenclatures): KitPropertiesDTO;
}
