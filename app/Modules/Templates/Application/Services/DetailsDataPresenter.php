<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Services;

use App\Modules\Templates\Application\Services\Presenters\AbstractDetailsPresenter;
use App\Modules\Templates\Application\Services\Presenters\Engine\AirFilterDetailsPresenter;
use App\Modules\Templates\Application\Services\Presenters\Engine\OilFilterDetailsPresenter;
use App\Modules\Templates\Application\Services\Presenters\Engine\SparkPlugDetailsPresenter;
use App\Modules\Templates\Application\Services\Presenters\Vehicle\WiperDetailsPresenter;
use App\Modules\Templates\Domain\Contracts\Services\DetailsDataPresenterInterface;
use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Templates\Domain\Enums\SparkPlug\ElectrodeGapEnum;
use App\Modules\Templates\Domain\Enums\SparkPlug\ThreadLengthEnum;
use App\Modules\Templates\Domain\Enums\SparkPlug\ThreadPitchEnum;
use App\Modules\Templates\Domain\Enums\SparkPlug\ThreadSizeEnum;
use App\Modules\Templates\Domain\Enums\SparkPlug\WrenchJawWidthEnum;
use App\Modules\Templates\Domain\Enums\Wiper\FrontAdapterTypeEnum;
use App\Modules\Templates\Domain\Enums\Wiper\RearAdapterTypeEnum;

/**
 * Selector: по `DetailTemplateEnum` выбирает презентер конкретного шаблона (`Presenters/*`, один
 * класс на шаблон — симметрично `DetailsDataFactory`/`Builders/*`). Презентеры — простые классы
 * без собственного порта (не резолвятся из контейнера независимо, вызываются только отсюда) —
 * конструируются по умолчанию.
 */
final readonly class DetailsDataPresenter implements DetailsDataPresenterInterface
{
    /**
     * Этот конструктор принимает presenters автомобильных details-шаблонов.
     * Шаги:
     * 1) Сохраняет presenter каждого поддержанного `DetailTemplateEnum` в отдельное поле.
     * 2) Использует дефолтные stateless-инстансы, потому что presenters вызываются только через
     *    этот selector и не требуют контейнерной конфигурации.
     */
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
        $presenter = $this->presenterFor($template);

        return $presenter->headings();
    }

    /**
     * Возвращает справочники select-полей vehicle details в тех же labels, что используют
     * presenters шаблона.
     * Шаги:
     * 1) Для дворников возвращает справочники типов переднего и заднего крепления.
     * 2) Для свечей возвращает справочники резьбы, электрода и ширины ключа.
     * 3) Для шаблонов без select-полей возвращает пустой массив.
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
        $presenter = $this->presenterFor($template);

        return $presenter->cellsFromDetails($details);
    }

    /**
     * Возвращает presenter конкретного vehicle-шаблона.
     * Шаги:
     * 1) Сопоставить enum шаблона с локальным presenter-ом.
     * 2) Вернуть общий базовый тип, через который публичные методы получают headings и cells.
     */
    private function presenterFor(DetailTemplateEnum $template): AbstractDetailsPresenter
    {
        return match ($template) {
            DetailTemplateEnum::WIPER => $this->wiper,
            DetailTemplateEnum::SPARK_PLUGS => $this->sparkPlugs,
            DetailTemplateEnum::OIL_FILTER => $this->oilFilter,
            DetailTemplateEnum::AIR_FILTER => $this->airFilter,
        };
    }

    /**
     * Этот метод возвращает Excel-лейблы backed enum-справочника.
     * Шаги:
     * 1) Получает все cases enum-класса.
     * 2) Берёт у каждого case его `value`, потому что именно value показывается в Excel.
     * 3) Возвращает список labels в порядке объявления enum.
     *
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
