<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\ModelData\Nomenclature;

use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;

/**
 * Форма шаблона `oilFilter` (Nomenclature). Портируется декларативно 1-в-1 из `OilFilterTemplate`
 * dan-center, без тестового покрытия — подключение к реальному Import/Export ещё не сделано.
 *
 * `father` — в dan-center `conditional_select`, чья видимость в форме зависела от `performance`
 * (WIND_UP → `Filter\OilFilterThreadEnum`, DIRECT_FLOW → `Filter\OilFilterFatherEnum`, LONG_TERM —
 * поле скрыто). Здесь — как и в `Vehicle`-версии этого же поля — плоское значение-строка, обе
 * стороны (сборка/рендер) пробуют оба словаря по очереди, не завязываясь на `performance`.
 *
 * Чистый объект-значение — сборка/рендер сюда не входят.
 */
final class OilFilterDetailsData extends AbstractDetailsData
{
    public function __construct(
        public readonly ?string $performance = null,
        public readonly ?string $form = null,
        public readonly ?bool $frame = null,
        public readonly ?string $father = null,
        public readonly ?int $diameter = null,
        public readonly ?int $mother = null,
        public readonly NomenclatureMetricsData $metrics = new NomenclatureMetricsData,
    ) {}
}
