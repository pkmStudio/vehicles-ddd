<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Packaging\Domain\Contracts\Services;

use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Modules\Warehouse\Features\Packaging\Domain\ModelData\TypeData;

/**
 * Порт определения detail-шаблона для Warehouse-типа номенклатуры (выбор стратегии упаковки).
 */
interface TypeTemplateResolverInterface
{
    /**
     * Возвращает шаблон details или null для типа без template-specific колонок.
     */
    public function resolve(TypeData $type): ?NomenclatureDetailTemplateEnum;
}
