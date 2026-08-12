<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Application\Services;

use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\ApplicabilityServiceFactoryInterface;
use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\Wiper\WiperApplicabilityServiceInterface;
use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;

final readonly class ApplicabilityServiceFactory implements ApplicabilityServiceFactoryInterface
{
    /**
     * Получает доступные алгоритмы расчета применяемости.
     *
     * Шаги:
     * 1. Сохраняет wiper algorithm service для комплектов дворников.
     * 2. Оставляет неподдержанные templates без fallback-алгоритма.
     */
    public function __construct(
        private WiperApplicabilityServiceInterface $wiper,
    ) {}

    /**
     * Возвращает service расчета для detail template комплекта.
     *
     * Шаги:
     * 1. Сопоставляет template дворников с wiper calculation service.
     * 2. Для остальных templates возвращает `null`, чтобы caller засчитал kit как skipped.
     */
    public function make(NomenclatureDetailTemplateEnum $template): ?WiperApplicabilityServiceInterface
    {
        return match ($template) {
            NomenclatureDetailTemplateEnum::WIPER => $this->wiper,
            default => null,
        };
    }
}
