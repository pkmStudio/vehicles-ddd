<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services;

use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\Wiper\WiperApplicabilityServiceInterface;
use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;

interface ApplicabilityServiceFactoryInterface
{
    /**
     * Выбирает алгоритм расчета применяемости по template складской номенклатуры.
     *
     * Шаги:
     * 1. Получает template комплекта из Warehouse type.
     * 2. Сопоставляет template с поддержанным calculation service.
     * 3. Возвращает service или `null` для неподдержанного template.
     */
    public function make(NomenclatureDetailTemplateEnum $template): ?WiperApplicabilityServiceInterface;
}
