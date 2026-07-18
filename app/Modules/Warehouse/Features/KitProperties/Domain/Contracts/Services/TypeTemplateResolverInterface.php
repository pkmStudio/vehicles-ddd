<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\KitProperties\Domain\Contracts\Services;

use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Modules\Warehouse\Features\KitProperties\Domain\ModelData\TypeData;

/**
 * Порт определения detail-шаблона для Warehouse-типа номенклатуры. Здесь нужен только
 * `WiperWithAdapterStrategy`, чтобы отличить WIPER от WIPER_ADAPTER по смыслу, а не по хардкод-id.
 */
interface TypeTemplateResolverInterface
{
    /**
     * Возвращает шаблон details или null для типа без template-specific колонок.
     */
    public function resolve(TypeData $type): ?NomenclatureDetailTemplateEnum;
}
