<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services;

use App\Modules\Applicability\Features\Calculation\Domain\ModelData\TypeData;
use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;

/**
 * Порт определения detail-шаблона для Warehouse-типа номенклатуры.
 */
interface TypeTemplateResolverInterface
{
    /**
     * Возвращает шаблон details или null для типа без template-specific колонок.
     */
    public function resolve(TypeData $type): ?NomenclatureDetailTemplateEnum;
}
