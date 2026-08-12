<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Services;

use App\Modules\Templates\Application\Services\Presenters\AbstractDetailsPresenter;
use App\Modules\Templates\Application\Services\Presenters\Nomenclature\AirFilterDetailsPresenter;
use App\Modules\Templates\Application\Services\Presenters\Nomenclature\BallJointDetailsPresenter;
use App\Modules\Templates\Application\Services\Presenters\Nomenclature\BrakePadDetailsPresenter;
use App\Modules\Templates\Application\Services\Presenters\Nomenclature\CabinFilterDetailsPresenter;
use App\Modules\Templates\Application\Services\Presenters\Nomenclature\CvJointDetailsPresenter;
use App\Modules\Templates\Application\Services\Presenters\Nomenclature\GenericDetailsPresenter;
use App\Modules\Templates\Application\Services\Presenters\Nomenclature\OilFilterDetailsPresenter;
use App\Modules\Templates\Application\Services\Presenters\Nomenclature\PolyVBeltDetailsPresenter;
use App\Modules\Templates\Application\Services\Presenters\Nomenclature\SparkPlugDetailsPresenter;
use App\Modules\Templates\Application\Services\Presenters\Nomenclature\StabilizerLinkDetailsPresenter;
use App\Modules\Templates\Application\Services\Presenters\Nomenclature\TieRodDetailsPresenter;
use App\Modules\Templates\Application\Services\Presenters\Nomenclature\TieRodEndDetailsPresenter;
use App\Modules\Templates\Application\Services\Presenters\Nomenclature\TimingBeltDetailsPresenter;
use App\Modules\Templates\Application\Services\Presenters\Nomenclature\WheelHubBearingDetailsPresenter;
use App\Modules\Templates\Application\Services\Presenters\Nomenclature\WheelHubDetailsPresenter;
use App\Modules\Templates\Application\Services\Presenters\Nomenclature\WiperAdapterDetailsPresenter;
use App\Modules\Templates\Application\Services\Presenters\Nomenclature\WiperDetailsPresenter;
use App\Modules\Templates\Domain\Contracts\EnumHelperInterface;
use App\Modules\Templates\Domain\Contracts\Services\NomenclatureDetailsDataPresenterInterface;
use App\Modules\Templates\Domain\Enums\BooleanOptionEnum;
use App\Modules\Templates\Domain\Enums\BrakePad\BrakePadTypeEnum;
use App\Modules\Templates\Domain\Enums\BrakePad\LiningMaterialEnum;
use App\Modules\Templates\Domain\Enums\Filter\FilterMediaTypeEnum;
use App\Modules\Templates\Domain\Enums\Filter\FormEnum;
use App\Modules\Templates\Domain\Enums\Filter\OilFilterFatherEnum;
use App\Modules\Templates\Domain\Enums\Filter\OilFilterThreadEnum;
use App\Modules\Templates\Domain\Enums\Filter\PerformanceEnum;
use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Modules\Templates\Domain\Enums\PositionEnum;
use App\Modules\Templates\Domain\Enums\SparkPlug\ElectrodeGapEnum;
use App\Modules\Templates\Domain\Enums\SparkPlug\ElectrodeSideCountEnum;
use App\Modules\Templates\Domain\Enums\SparkPlug\ThreadLengthEnum;
use App\Modules\Templates\Domain\Enums\SparkPlug\ThreadPitchEnum;
use App\Modules\Templates\Domain\Enums\SparkPlug\ThreadSizeEnum;
use App\Modules\Templates\Domain\Enums\SparkPlug\WrenchJawWidthEnum;
use App\Modules\Templates\Domain\Enums\TieRod\ApplicationEnum;
use App\Modules\Templates\Domain\Enums\Wiper\CategoryEnum;
use App\Modules\Templates\Domain\Enums\Wiper\ConstructionEnum;
use App\Modules\Templates\Domain\Enums\Wiper\FrontAdapterTypeEnum;
use App\Modules\Templates\Domain\Enums\Wiper\RearAdapterTypeEnum;
use App\Modules\Templates\Domain\Enums\Wiper\SeasonEnum;
use App\Modules\Templates\Domain\Enums\Wiper\SteeringCompatibilityEnum;

/**
 * Selector: по `NomenclatureDetailTemplateEnum` выбирает презентер конкретного шаблона
 * (`Presenters/Nomenclature/*`, симметрично `NomenclatureDetailsDataFactory`/`Builders/Nomenclature/*`).
 */
final readonly class NomenclatureDetailsDataPresenter implements NomenclatureDetailsDataPresenterInterface
{
    public function __construct(
        private BrakePadDetailsPresenter $brakePads = new BrakePadDetailsPresenter,
        private SparkPlugDetailsPresenter $sparkPlugs = new SparkPlugDetailsPresenter,
        private WiperDetailsPresenter $wiper = new WiperDetailsPresenter,
        private OilFilterDetailsPresenter $oilFilter = new OilFilterDetailsPresenter,
        private AirFilterDetailsPresenter $airFilter = new AirFilterDetailsPresenter,
        private CabinFilterDetailsPresenter $cabinFilter = new CabinFilterDetailsPresenter,
        private WiperAdapterDetailsPresenter $wiperAdapter = new WiperAdapterDetailsPresenter,
        private TimingBeltDetailsPresenter $timingBelt = new TimingBeltDetailsPresenter,
        private GenericDetailsPresenter $generic = new GenericDetailsPresenter,
        private WheelHubBearingDetailsPresenter $wheelHubBearing = new WheelHubBearingDetailsPresenter,
        private WheelHubDetailsPresenter $wheelHub = new WheelHubDetailsPresenter,
        private TieRodEndDetailsPresenter $tieRodEnd = new TieRodEndDetailsPresenter,
        private TieRodDetailsPresenter $tieRod = new TieRodDetailsPresenter,
        private StabilizerLinkDetailsPresenter $stabilizerLink = new StabilizerLinkDetailsPresenter,
        private BallJointDetailsPresenter $ballJoint = new BallJointDetailsPresenter,
        private CvJointDetailsPresenter $cvJoint = new CvJointDetailsPresenter,
        private PolyVBeltDetailsPresenter $polyVBelt = new PolyVBeltDetailsPresenter,
    ) {}

    public function headingsFor(NomenclatureDetailTemplateEnum $template): array
    {
        $presenter = $this->presenterFor($template);

        return $presenter->headings();
    }

    /**
     * Возвращает справочники select-полей в тех же labels, что используют presenters шаблона.
     *
     * @return array<string, list<string>>
     */
    public function referenceOptionsFor(NomenclatureDetailTemplateEnum $template): array
    {
        return match ($template) {
            NomenclatureDetailTemplateEnum::BRAKE_PADS => [
                'Расположение' => $this->labels(PositionEnum::class),
                'Вид колодки' => $this->labels(BrakePadTypeEnum::class),
                'Материал накладок' => $this->labels(LiningMaterialEnum::class),
            ],
            NomenclatureDetailTemplateEnum::SPARK_PLUGS => [
                'Размер резьбы' => $this->labels(ThreadSizeEnum::class),
                'Шаг резьбы (мм)' => $this->labels(ThreadPitchEnum::class),
                'Длина резьбы (мм)' => $this->labels(ThreadLengthEnum::class),
                'Межконтактный зазор (мм)' => $this->labels(ElectrodeGapEnum::class),
                'Число боковых электродов' => $this->labels(ElectrodeSideCountEnum::class),
                'Ширина зева гаечного ключа (мм)' => $this->labels(WrenchJawWidthEnum::class),
            ],
            NomenclatureDetailTemplateEnum::WIPER => [
                'Расположение' => $this->labels(PositionEnum::class),
                'Категория' => $this->labels(CategoryEnum::class),
                'Конструкция' => $this->labels(ConstructionEnum::class),
                'Сезон' => $this->labels(SeasonEnum::class),
                'Тип крепления передних' => $this->labels(FrontAdapterTypeEnum::class),
                'Тип крепления задней' => $this->labels(RearAdapterTypeEnum::class),
                'Датчик износа' => $this->labels(BooleanOptionEnum::class),
                'Спойлер' => $this->labels(BooleanOptionEnum::class),
                'Форсунка омывателя' => $this->labels(BooleanOptionEnum::class),
                'C подогревом' => $this->labels(BooleanOptionEnum::class),
                'Рулевое управление' => $this->labels(SteeringCompatibilityEnum::class),
            ],
            NomenclatureDetailTemplateEnum::OIL_FILTER => [
                'Исполнение фильтра' => $this->labels(PerformanceEnum::class),
                'Форма фильтра' => $this->labels(FormEnum::class),
                'Корпус' => $this->labels(BooleanOptionEnum::class),
                'Резьба или Папа' => $this->oilFilterThreadOrFatherOptions(),
            ],
            NomenclatureDetailTemplateEnum::AIR_FILTER,
            NomenclatureDetailTemplateEnum::CABIN_FILTER => [
                'Исполнение фильтра' => $this->labels(PerformanceEnum::class),
                'Форма фильтра' => $this->labels(FormEnum::class),
                'Корпус' => $this->labels(BooleanOptionEnum::class),
                'Вид фильтра' => $this->labels(FilterMediaTypeEnum::class),
            ],
            NomenclatureDetailTemplateEnum::WIPER_ADAPTER => [
                'Расположение' => $this->labels(PositionEnum::class),
                'Тип крепления передних' => $this->labels(FrontAdapterTypeEnum::class),
            ],
            NomenclatureDetailTemplateEnum::TIE_ROD => [
                'Применение' => $this->labels(ApplicationEnum::class),
            ],
            default => [],
        };
    }

    /**
     * Этот метод рендерит details номенклатурного шаблона в плоский набор Excel-ячеек экспорта.
     * Шаги:
     * 1) По типу шаблона собирает именованный `*DetailsData` из сохраненного plain-массива.
     * 2) Передает типизированный объект в соответствующий presenter.
     * 3) Возвращает готовые значения Excel-ячеек без изменения wire shape.
     */
    public function toExportCells(NomenclatureDetailTemplateEnum $template, array $details): array
    {
        $presenter = $this->presenterFor($template);

        return $presenter->cellsFromDetails($details);
    }

    /**
     * Возвращает presenter конкретного номенклатурного шаблона.
     * Шаги:
     * 1) Сопоставить enum шаблона с локальным presenter-ом.
     * 2) Вернуть общий базовый тип, через который публичные методы получают headings и cells.
     */
    private function presenterFor(NomenclatureDetailTemplateEnum $template): AbstractDetailsPresenter
    {
        return match ($template) {
            NomenclatureDetailTemplateEnum::BRAKE_PADS => $this->brakePads,
            NomenclatureDetailTemplateEnum::SPARK_PLUGS => $this->sparkPlugs,
            NomenclatureDetailTemplateEnum::WIPER => $this->wiper,
            NomenclatureDetailTemplateEnum::OIL_FILTER => $this->oilFilter,
            NomenclatureDetailTemplateEnum::AIR_FILTER => $this->airFilter,
            NomenclatureDetailTemplateEnum::CABIN_FILTER => $this->cabinFilter,
            NomenclatureDetailTemplateEnum::WIPER_ADAPTER => $this->wiperAdapter,
            NomenclatureDetailTemplateEnum::TIMING_BELT => $this->timingBelt,
            NomenclatureDetailTemplateEnum::V_BELT => $this->generic,
            NomenclatureDetailTemplateEnum::WHEEL_HUB_BEARING => $this->wheelHubBearing,
            NomenclatureDetailTemplateEnum::WHEEL_HUB => $this->wheelHub,
            NomenclatureDetailTemplateEnum::TIE_ROD_END => $this->tieRodEnd,
            NomenclatureDetailTemplateEnum::TIE_ROD => $this->tieRod,
            NomenclatureDetailTemplateEnum::STABILIZER_LINK => $this->stabilizerLink,
            NomenclatureDetailTemplateEnum::BALL_JOINT => $this->ballJoint,
            NomenclatureDetailTemplateEnum::CV_JOINT => $this->cvJoint,
            NomenclatureDetailTemplateEnum::POLY_V_BELT => $this->polyVBelt,
        };
    }

    /**
     * Возвращает объединенный справочник резьбы и папы для масляного фильтра.
     * Шаги:
     * 1) Собрать labels двух enum-справочников.
     * 2) Удалить дубли и вернуть список в формате Excel reference options.
     *
     * @return list<string>
     */
    private function oilFilterThreadOrFatherOptions(): array
    {
        $threadOptions = $this->labels(OilFilterThreadEnum::class);
        $fatherOptions = $this->labels(OilFilterFatherEnum::class);
        $mergedOptions = array_merge($threadOptions, $fatherOptions);

        return array_values(array_unique($mergedOptions));
    }

    /**
     * Возвращает Excel-лейблы enum-справочника.
     *
     * @param  class-string<EnumHelperInterface>  $enumClass
     * @return list<string>
     */
    private function labels(string $enumClass): array
    {
        $toValue = fn (EnumHelperInterface $case): string => $case->value;

        return array_map(
            $toValue,
            $enumClass::cases(),
        );
    }
}
