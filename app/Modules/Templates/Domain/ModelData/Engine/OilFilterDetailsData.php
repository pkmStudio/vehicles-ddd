<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\ModelData\Engine;

use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;

/**
 * Форма шаблона `oilFilter`. Сегодня НЕ подключена ни к одному Import/Export сценарию (проверено
 * по всему `app/`), портируется декларативно 1-в-1 из старого `OilFilterTemplate`, без тестового
 * покрытия. При реальном подключении сценария — перепроверить отдельно.
 *
 * `father` — старый DSL держал его как `conditional_select`, чья видимость в Filament-форме
 * зависела от `performance` (WIND_UP → OilFilterThreadEnum, DIRECT_FLOW → OilFilterFatherEnum).
 * Но сам парсинг значения (в headless `DetailsBuilder`) эту зависимость никогда не проверял —
 * `options_source` мержился целиком независимо от текущего `performance` (см. старый
 * `getFieldValue()`: `array_replace` по всем источникам без всякого `if`). Сборка/рендер
 * (`DetailsDataFactory`/`DetailsDataPresenter`) воспроизводят то же: пробуют оба словаря по
 * очереди, не завязываясь на `performance`.
 *
 * Чистый объект-значение — сборка из строки и рендер в Excel-ячейки сюда не входят.
 */
final class OilFilterDetailsData extends AbstractDetailsData
{
    public function __construct(
        public readonly ?string $performance = null,
        public readonly ?string $form = null,
        public readonly ?string $father = null,
        public readonly ?int $diameter = null,
        public readonly ?int $mother = null,
        public readonly OilFilterMetricsData $metrics = new OilFilterMetricsData,
    ) {}
}
