<?php

declare(strict_types=1);

namespace App\Vehicles\Templates\Application\Services;

use App\Vehicles\Templates\Application\Services\Presenters\AirFilterDetailsPresenter;
use App\Vehicles\Templates\Application\Services\Presenters\OilFilterDetailsPresenter;
use App\Vehicles\Templates\Application\Services\Presenters\SparkPlugDetailsPresenter;
use App\Vehicles\Templates\Application\Services\Presenters\WiperDetailsPresenter;
use App\Vehicles\Templates\Domain\Contracts\Services\DetailsDataPresenterInterface;
use App\Vehicles\Templates\Domain\Enums\DetailTemplateEnum;
use App\Vehicles\Templates\Domain\ModelData\Engine\AirFilterDetailsData;
use App\Vehicles\Templates\Domain\ModelData\Engine\OilFilterDetailsData;
use App\Vehicles\Templates\Domain\ModelData\Engine\SparkPlugDetailsData;
use App\Vehicles\Templates\Domain\ModelData\Vehicle\WiperDetailsData;

/**
 * Selector: по `DetailTemplateEnum` выбирает презентер конкретного шаблона (`Presenters/*`, один
 * класс на шаблон — симметрично `DetailsDataFactory`/`Builders/*`). Презентеры — простые классы
 * без собственного порта (не резолвятся из контейнера независимо, вызываются только отсюда) —
 * конструируются по умолчанию.
 */
final readonly class DetailsDataPresenter implements DetailsDataPresenterInterface
{
    public function __construct(
        private WiperDetailsPresenter $wiper = new WiperDetailsPresenter,
        private SparkPlugDetailsPresenter $sparkPlugs = new SparkPlugDetailsPresenter,
        private OilFilterDetailsPresenter $oilFilter = new OilFilterDetailsPresenter,
        private AirFilterDetailsPresenter $airFilter = new AirFilterDetailsPresenter,
    ) {}

    /**
     * Этот метод отдаёт полный список заголовков Excel-колонок для конкретного шаблона.
     * Шаги:
     * 1) По `match` вызывает презентер, соответствующий шаблону.
     */
    public function headingsFor(DetailTemplateEnum $template): array
    {
        return match ($template) {
            DetailTemplateEnum::WIPER => $this->wiper->headings(),
            DetailTemplateEnum::SPARK_PLUGS => $this->sparkPlugs->headings(),
            DetailTemplateEnum::OIL_FILTER => $this->oilFilter->headings(),
            DetailTemplateEnum::AIR_FILTER => $this->airFilter->headings(),
        };
    }

    /**
     * Этот метод рендерит details конкретного шаблона в плоский набор Excel-ячеек экспорта.
     * Шаги:
     * 1) По `match` строит типизированный `<X>DetailsData` из сохранённого plain-массива
     *    (`::from()` — стандартный механизм spatie/laravel-data, не наша логика).
     * 2) Вызывает презентер, соответствующий шаблону.
     */
    public function toExportCells(DetailTemplateEnum $template, array $details): array
    {
        return match ($template) {
            DetailTemplateEnum::WIPER => $this->wiper->cells(WiperDetailsData::from($details)),
            DetailTemplateEnum::SPARK_PLUGS => $this->sparkPlugs->cells(SparkPlugDetailsData::from($details)),
            DetailTemplateEnum::OIL_FILTER => $this->oilFilter->cells(OilFilterDetailsData::from($details)),
            DetailTemplateEnum::AIR_FILTER => $this->airFilter->cells(AirFilterDetailsData::from($details)),
        };
    }
}
