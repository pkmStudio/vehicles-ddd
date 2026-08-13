<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Factories;

use App\Modules\Templates\Application\Factories\Builders\Nomenclature\AirFilterDetailsBuilder;
use App\Modules\Templates\Application\Factories\Builders\Nomenclature\BallJointDetailsBuilder;
use App\Modules\Templates\Application\Factories\Builders\Nomenclature\BrakePadDetailsBuilder;
use App\Modules\Templates\Application\Factories\Builders\Nomenclature\CabinFilterDetailsBuilder;
use App\Modules\Templates\Application\Factories\Builders\Nomenclature\CvJointDetailsBuilder;
use App\Modules\Templates\Application\Factories\Builders\Nomenclature\GenericDetailsBuilder;
use App\Modules\Templates\Application\Factories\Builders\Nomenclature\OilFilterDetailsBuilder;
use App\Modules\Templates\Application\Factories\Builders\Nomenclature\PolyVBeltDetailsBuilder;
use App\Modules\Templates\Application\Factories\Builders\Nomenclature\SparkPlugDetailsBuilder;
use App\Modules\Templates\Application\Factories\Builders\Nomenclature\StabilizerLinkDetailsBuilder;
use App\Modules\Templates\Application\Factories\Builders\Nomenclature\TieRodDetailsBuilder;
use App\Modules\Templates\Application\Factories\Builders\Nomenclature\TieRodEndDetailsBuilder;
use App\Modules\Templates\Application\Factories\Builders\Nomenclature\TimingBeltDetailsBuilder;
use App\Modules\Templates\Application\Factories\Builders\Nomenclature\WheelHubBearingDetailsBuilder;
use App\Modules\Templates\Application\Factories\Builders\Nomenclature\WheelHubDetailsBuilder;
use App\Modules\Templates\Application\Factories\Builders\Nomenclature\WiperAdapterDetailsBuilder;
use App\Modules\Templates\Application\Factories\Builders\Nomenclature\WiperDetailsBuilder;
use App\Modules\Templates\Domain\Contracts\Factories\NomenclatureDetailsDataFactoryInterface;
use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;

/**
 * Selector: по `NomenclatureDetailTemplateEnum` выбирает билдер конкретного шаблона (один класс
 * на шаблон, симметрично `DetailsDataFactory`/`Builders/*`). Билдеры — простые классы без
 * собственного порта, конструируются по умолчанию.
 */
final readonly class NomenclatureDetailsDataFactory implements NomenclatureDetailsDataFactoryInterface
{
    /**
     * Этот конструктор принимает набор билдеров номенклатурных details-шаблонов.
     * Шаги:
     * 1) Сохраняет каждый билдер в поле, соответствующее конкретному enum-шаблону.
     * 2) Использует дефолтные инстансы, потому что билдеры не имеют собственного состояния и
     *    вызываются только этим selector-классом.
     */
    public function __construct(
        private BrakePadDetailsBuilder $brakePads = new BrakePadDetailsBuilder,
        private SparkPlugDetailsBuilder $sparkPlugs = new SparkPlugDetailsBuilder,
        private WiperDetailsBuilder $wiper = new WiperDetailsBuilder,
        private OilFilterDetailsBuilder $oilFilter = new OilFilterDetailsBuilder,
        private AirFilterDetailsBuilder $airFilter = new AirFilterDetailsBuilder,
        private CabinFilterDetailsBuilder $cabinFilter = new CabinFilterDetailsBuilder,
        private WiperAdapterDetailsBuilder $wiperAdapter = new WiperAdapterDetailsBuilder,
        private TimingBeltDetailsBuilder $timingBelt = new TimingBeltDetailsBuilder,
        private GenericDetailsBuilder $generic = new GenericDetailsBuilder,
        private WheelHubBearingDetailsBuilder $wheelHubBearing = new WheelHubBearingDetailsBuilder,
        private WheelHubDetailsBuilder $wheelHub = new WheelHubDetailsBuilder,
        private TieRodEndDetailsBuilder $tieRodEnd = new TieRodEndDetailsBuilder,
        private TieRodDetailsBuilder $tieRod = new TieRodDetailsBuilder,
        private StabilizerLinkDetailsBuilder $stabilizerLink = new StabilizerLinkDetailsBuilder,
        private BallJointDetailsBuilder $ballJoint = new BallJointDetailsBuilder,
        private CvJointDetailsBuilder $cvJoint = new CvJointDetailsBuilder,
        private PolyVBeltDetailsBuilder $polyVBelt = new PolyVBeltDetailsBuilder,
    ) {}

    /**
     * Этот метод строит details конкретного шаблона Nomenclature из Excel-строки и отдаёт
     * типизированный объект (не `array`).
     * Шаги:
     * 1) Заводит курсор чтения строки, начиная с переданной позиции.
     * 2) По `match` вызывает билдер, соответствующий шаблону.
     * 3) Синхронизирует внешнюю ссылку `&$index` с итоговой позицией курсора.
     * 4) Возвращает собранный типизированный объект.
     */
    public function make(NomenclatureDetailTemplateEnum $template, array $row, int &$index): AbstractDetailsData
    {
        $cursor = new DetailsRowCursor($row, $index);

        $data = match ($template) {
            NomenclatureDetailTemplateEnum::BRAKE_PADS => $this->brakePads->build($cursor),
            NomenclatureDetailTemplateEnum::SPARK_PLUGS => $this->sparkPlugs->build($cursor),
            NomenclatureDetailTemplateEnum::WIPER => $this->wiper->build($cursor),
            NomenclatureDetailTemplateEnum::OIL_FILTER => $this->oilFilter->build($cursor),
            NomenclatureDetailTemplateEnum::AIR_FILTER => $this->airFilter->build($cursor),
            NomenclatureDetailTemplateEnum::CABIN_FILTER => $this->cabinFilter->build($cursor),
            NomenclatureDetailTemplateEnum::WIPER_ADAPTER => $this->wiperAdapter->build($cursor),
            NomenclatureDetailTemplateEnum::TIMING_BELT => $this->timingBelt->build($cursor),
            NomenclatureDetailTemplateEnum::V_BELT => $this->generic->build($cursor),
            NomenclatureDetailTemplateEnum::WHEEL_HUB_BEARING => $this->wheelHubBearing->build($cursor),
            NomenclatureDetailTemplateEnum::WHEEL_HUB => $this->wheelHub->build($cursor),
            NomenclatureDetailTemplateEnum::TIE_ROD_END => $this->tieRodEnd->build($cursor),
            NomenclatureDetailTemplateEnum::TIE_ROD => $this->tieRod->build($cursor),
            NomenclatureDetailTemplateEnum::STABILIZER_LINK => $this->stabilizerLink->build($cursor),
            NomenclatureDetailTemplateEnum::BALL_JOINT => $this->ballJoint->build($cursor),
            NomenclatureDetailTemplateEnum::CV_JOINT => $this->cvJoint->build($cursor),
            NomenclatureDetailTemplateEnum::POLY_V_BELT => $this->polyVBelt->build($cursor),
        };

        $index = $cursor->position();

        return $data;
    }
}
