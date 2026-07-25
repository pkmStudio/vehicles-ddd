<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Services;

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
use App\Modules\Templates\Domain\ModelData\Nomenclature\AirFilterDetailsData;
use App\Modules\Templates\Domain\ModelData\Nomenclature\BallJointDetailsData;
use App\Modules\Templates\Domain\ModelData\Nomenclature\BrakePadDetailsData;
use App\Modules\Templates\Domain\ModelData\Nomenclature\CabinFilterDetailsData;
use App\Modules\Templates\Domain\ModelData\Nomenclature\CvJointDetailsData;
use App\Modules\Templates\Domain\ModelData\Nomenclature\GenericDetailsData;
use App\Modules\Templates\Domain\ModelData\Nomenclature\OilFilterDetailsData;
use App\Modules\Templates\Domain\ModelData\Nomenclature\PolyVBeltDetailsData;
use App\Modules\Templates\Domain\ModelData\Nomenclature\SparkPlugDetailsData;
use App\Modules\Templates\Domain\ModelData\Nomenclature\StabilizerLinkDetailsData;
use App\Modules\Templates\Domain\ModelData\Nomenclature\TieRodDetailsData;
use App\Modules\Templates\Domain\ModelData\Nomenclature\TieRodEndDetailsData;
use App\Modules\Templates\Domain\ModelData\Nomenclature\TimingBeltDetailsData;
use App\Modules\Templates\Domain\ModelData\Nomenclature\WheelHubBearingDetailsData;
use App\Modules\Templates\Domain\ModelData\Nomenclature\WheelHubDetailsData;
use App\Modules\Templates\Domain\ModelData\Nomenclature\WiperAdapterDetailsData;
use App\Modules\Templates\Domain\ModelData\Nomenclature\WiperDetailsData;

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
        return match ($template) {
            NomenclatureDetailTemplateEnum::BRAKE_PADS => $this->brakePads->headings(),
            NomenclatureDetailTemplateEnum::SPARK_PLUGS => $this->sparkPlugs->headings(),
            NomenclatureDetailTemplateEnum::WIPER => $this->wiper->headings(),
            NomenclatureDetailTemplateEnum::OIL_FILTER => $this->oilFilter->headings(),
            NomenclatureDetailTemplateEnum::AIR_FILTER => $this->airFilter->headings(),
            NomenclatureDetailTemplateEnum::CABIN_FILTER => $this->cabinFilter->headings(),
            NomenclatureDetailTemplateEnum::WIPER_ADAPTER => $this->wiperAdapter->headings(),
            NomenclatureDetailTemplateEnum::TIMING_BELT => $this->timingBelt->headings(),
            NomenclatureDetailTemplateEnum::V_BELT => $this->generic->headings(),
            NomenclatureDetailTemplateEnum::WHEEL_HUB_BEARING => $this->wheelHubBearing->headings(),
            NomenclatureDetailTemplateEnum::WHEEL_HUB => $this->wheelHub->headings(),
            NomenclatureDetailTemplateEnum::TIE_ROD_END => $this->tieRodEnd->headings(),
            NomenclatureDetailTemplateEnum::TIE_ROD => $this->tieRod->headings(),
            NomenclatureDetailTemplateEnum::STABILIZER_LINK => $this->stabilizerLink->headings(),
            NomenclatureDetailTemplateEnum::BALL_JOINT => $this->ballJoint->headings(),
            NomenclatureDetailTemplateEnum::CV_JOINT => $this->cvJoint->headings(),
            NomenclatureDetailTemplateEnum::POLY_V_BELT => $this->polyVBelt->headings(),
        };
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
                'Резьба или Папа' => array_values(array_unique(array_merge(
                    $this->labels(OilFilterThreadEnum::class),
                    $this->labels(OilFilterFatherEnum::class),
                ))),
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
                'Конструкция' => $this->labels(ConstructionEnum::class),
                'Тип крепления передних' => $this->labels(FrontAdapterTypeEnum::class),
            ],
            NomenclatureDetailTemplateEnum::TIE_ROD => [
                'Применение' => $this->labels(ApplicationEnum::class),
            ],
            default => [],
        };
    }

    public function toExportCells(NomenclatureDetailTemplateEnum $template, array $details): array
    {
        return match ($template) {
            NomenclatureDetailTemplateEnum::BRAKE_PADS => $this->brakePads->cells(BrakePadDetailsData::from($details)),
            NomenclatureDetailTemplateEnum::SPARK_PLUGS => $this->sparkPlugs->cells(SparkPlugDetailsData::from($details)),
            NomenclatureDetailTemplateEnum::WIPER => $this->wiper->cells(WiperDetailsData::from($details)),
            NomenclatureDetailTemplateEnum::OIL_FILTER => $this->oilFilter->cells(OilFilterDetailsData::from($details)),
            NomenclatureDetailTemplateEnum::AIR_FILTER => $this->airFilter->cells(AirFilterDetailsData::from($details)),
            NomenclatureDetailTemplateEnum::CABIN_FILTER => $this->cabinFilter->cells(CabinFilterDetailsData::from($details)),
            NomenclatureDetailTemplateEnum::WIPER_ADAPTER => $this->wiperAdapter->cells(WiperAdapterDetailsData::from($details)),
            NomenclatureDetailTemplateEnum::TIMING_BELT => $this->timingBelt->cells(TimingBeltDetailsData::from($details)),
            NomenclatureDetailTemplateEnum::V_BELT => $this->generic->cells(GenericDetailsData::from($details)),
            NomenclatureDetailTemplateEnum::WHEEL_HUB_BEARING => $this->wheelHubBearing->cells(WheelHubBearingDetailsData::from($details)),
            NomenclatureDetailTemplateEnum::WHEEL_HUB => $this->wheelHub->cells(WheelHubDetailsData::from($details)),
            NomenclatureDetailTemplateEnum::TIE_ROD_END => $this->tieRodEnd->cells(TieRodEndDetailsData::from($details)),
            NomenclatureDetailTemplateEnum::TIE_ROD => $this->tieRod->cells(TieRodDetailsData::from($details)),
            NomenclatureDetailTemplateEnum::STABILIZER_LINK => $this->stabilizerLink->cells(StabilizerLinkDetailsData::from($details)),
            NomenclatureDetailTemplateEnum::BALL_JOINT => $this->ballJoint->cells(BallJointDetailsData::from($details)),
            NomenclatureDetailTemplateEnum::CV_JOINT => $this->cvJoint->cells(CvJointDetailsData::from($details)),
            NomenclatureDetailTemplateEnum::POLY_V_BELT => $this->polyVBelt->cells(PolyVBeltDetailsData::from($details)),
        };
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
