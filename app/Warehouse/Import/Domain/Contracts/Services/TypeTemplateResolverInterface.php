<?php

declare(strict_types=1);

namespace App\Warehouse\Import\Domain\Contracts\Services;

use App\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Warehouse\Import\Domain\ModelData\TypeData;

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
