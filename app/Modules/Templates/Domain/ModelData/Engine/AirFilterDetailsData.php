<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\ModelData\Engine;

use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;

/**
 * Форма шаблона `airFilter`. Сегодня НЕ подключена ни к одному Import/Export сценарию (проверено
 * по всему `app/` — только объявление `DetailTemplateEnum::AIR_FILTER`), портируется декларативно
 * 1-в-1 из старого `AirFilterTemplate`, без тестового покрытия. При реальном подключении сценария
 * — перепроверить отдельно. Чистый объект-значение — сборка из строки (`DetailsDataFactory`) и
 * рендер в Excel-ячейки (`DetailsDataPresenter`) сюда не входят.
 */
final class AirFilterDetailsData extends AbstractDetailsData
{
    public function __construct(
        public readonly string $form,
        /** @var array<int, float> */
        public readonly array $length,
        /** @var array<int, float> */
        public readonly array $width,
        /** @var array<int, float> */
        public readonly array $height,
        public readonly float $diameter,
    ) {}
}
