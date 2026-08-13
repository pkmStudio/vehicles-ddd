<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Application\Services;

use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\ApplicabilityServiceFactoryInterface;
use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\KitApplicabilityCalculatorInterface;
use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Calculation\KitApplicabilityKitResultDTO;
use App\Modules\Applicability\Features\Calculation\Domain\ModelData\KitData;

final readonly class KitApplicabilityCalculator implements KitApplicabilityCalculatorInterface
{
    /**
     * Получает factory алгоритмов применяемости по template комплекта.
     *
     * Шаги:
     * 1. Сохраняет factory, которая знает поддержанные detail templates.
     * 2. Оставляет выбор конкретного алгоритма методу `calculate()`.
     */
    public function __construct(
        private ApplicabilityServiceFactoryInterface $factory,
    ) {}

    /**
     * Рассчитывает применяемость одного Warehouse kit через подходящий алгоритм.
     *
     * Шаги:
     * 1. Если у kit нет template, возвращает `null` как неподдержанный расчет.
     * 2. Запрашивает service для template комплекта.
     * 3. Если service не найден, возвращает `null`.
     * 4. Делегирует расчет template-specific service-у.
     */
    public function calculate(KitData $kit): ?KitApplicabilityKitResultDTO
    {
        if ($kit->template === null) {
            return null;
        }

        $service = $this->factory->make($kit->template);

        return $service?->calculate($kit);
    }
}
