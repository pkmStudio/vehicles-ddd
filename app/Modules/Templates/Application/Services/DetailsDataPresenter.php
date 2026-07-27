<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Services;

use App\Modules\Templates\Application\Services\Presenters\AirFilterDetailsPresenter;
use App\Modules\Templates\Application\Services\Presenters\OilFilterDetailsPresenter;
use App\Modules\Templates\Application\Services\Presenters\SparkPlugDetailsPresenter;
use App\Modules\Templates\Application\Services\Presenters\WiperDetailsPresenter;
use App\Modules\Templates\Domain\Contracts\Services\DetailsDataPresenterInterface;
use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Templates\Domain\Enums\SparkPlug\ElectrodeGapEnum;
use App\Modules\Templates\Domain\Enums\SparkPlug\ThreadLengthEnum;
use App\Modules\Templates\Domain\Enums\SparkPlug\ThreadPitchEnum;
use App\Modules\Templates\Domain\Enums\SparkPlug\ThreadSizeEnum;
use App\Modules\Templates\Domain\Enums\SparkPlug\WrenchJawWidthEnum;
use App\Modules\Templates\Domain\Enums\Wiper\FrontAdapterTypeEnum;
use App\Modules\Templates\Domain\Enums\Wiper\RearAdapterTypeEnum;
use App\Modules\Templates\Domain\ModelData\Engine\AirFilterDetailsData;
use App\Modules\Templates\Domain\ModelData\Engine\OilFilterDetailsData;
use App\Modules\Templates\Domain\ModelData\Engine\SparkPlugDetailsData;
use App\Modules\Templates\Domain\ModelData\Vehicle\WiperDetailsData;

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
     * Возвращает справочники select-полей vehicle details в тех же labels, что используют
     * presenters шаблона.
     *
     * @return array<string, list<string>>
     */
    public function referenceOptionsFor(DetailTemplateEnum $template): array
    {
        return match ($template) {
            DetailTemplateEnum::WIPER => [
                'Тип крепления передних' => $this->labels(FrontAdapterTypeEnum::class),
                'Тип крепления задней' => $this->labels(RearAdapterTypeEnum::class),
            ],
            DetailTemplateEnum::SPARK_PLUGS => [
                'Размер резьбы' => $this->labels(ThreadSizeEnum::class),
                'Шаг резьбы (мм)' => $this->labels(ThreadPitchEnum::class),
                'Длина резьбы (мм)' => $this->labels(ThreadLengthEnum::class),
                'Межконтактный зазор (мм)' => $this->labels(ElectrodeGapEnum::class),
                'Ширина зева гаечного ключа (мм)' => $this->labels(WrenchJawWidthEnum::class),
            ],
            default => [],
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

    /**
     * @param  class-string<\BackedEnum>  $enumClass
     * @return list<string>
     */
    private function labels(string $enumClass): array
    {
        return array_map(
            static fn (\BackedEnum $case): string => (string) $case->value,
            $enumClass::cases(),
        );
    }
}
