<?php

declare(strict_types=1);

namespace App\Warehouse\Packaging\Application\Services;

use App\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Warehouse\Packaging\Application\Services\Strategies\AirFilterPackagingStrategy;
use App\Warehouse\Packaging\Application\Services\Strategies\BrakePadsPackagingStrategy;
use App\Warehouse\Packaging\Application\Services\Strategies\CabinFilterPackagingStrategy;
use App\Warehouse\Packaging\Application\Services\Strategies\GenericPackagingStrategy;
use App\Warehouse\Packaging\Application\Services\Strategies\OilFilterPackagingStrategy;
use App\Warehouse\Packaging\Application\Services\Strategies\SparkPlugsPackagingStrategy;
use App\Warehouse\Packaging\Application\Services\Strategies\WiperAdapterPackagingStrategy;
use App\Warehouse\Packaging\Application\Services\Strategies\WiperPackagingStrategy;
use App\Warehouse\Packaging\Domain\Contracts\Repositories\PackDimensionRepositoryInterface;
use App\Warehouse\Packaging\Domain\Contracts\Services\PackagingServiceInterface;
use App\Warehouse\Packaging\Domain\Contracts\Services\TypeTemplateResolverInterface;
use App\Warehouse\Packaging\Domain\ModelData\NomenclatureData;
use App\Warehouse\Packaging\Domain\ModelData\PackDimensionData;
use App\Warehouse\Packaging\Domain\ModelData\TypeData;

/**
 * Selector: по detail-шаблону типа выбирает конкретную стратегию подбора упаковки (одна стратегия
 * на шаблон, симметрично `Templates\NomenclatureDetailsDataFactory`). Стратегии — простые классы
 * без собственного порта, конструируются по умолчанию.
 */
final readonly class PackagingService implements PackagingServiceInterface
{
    public function __construct(
        private PackDimensionRepositoryInterface $repository,
        private TypeTemplateResolverInterface $templateResolver,
        private BrakePadsPackagingStrategy $brakePads,
        private WiperPackagingStrategy $wiper,
        private CabinFilterPackagingStrategy $cabinFilter,
        private OilFilterPackagingStrategy $oilFilter,
        private GenericPackagingStrategy $generic,
        private SparkPlugsPackagingStrategy $sparkPlugs,
        private WiperAdapterPackagingStrategy $wiperAdapter,
        private AirFilterPackagingStrategy $airFilter,
    ) {}

    /**
     * Этот метод резолвит detail-шаблон типа, читает упаковки этого типа и делегирует расчёт
     * соответствующей стратегии.
     *
     * @param  array<int, NomenclatureData>  $nomenclatures
     */
    public function selectOrCreate(TypeData $type, array $nomenclatures): PackDimensionData
    {
        $template = $this->templateResolver->resolve($type);
        $packDimensions = $this->repository->byType($type);

        $strategy = match ($template) {
            NomenclatureDetailTemplateEnum::BRAKE_PADS => $this->brakePads,
            NomenclatureDetailTemplateEnum::WIPER => $this->wiper,
            NomenclatureDetailTemplateEnum::CABIN_FILTER => $this->cabinFilter,
            NomenclatureDetailTemplateEnum::OIL_FILTER => $this->oilFilter,
            NomenclatureDetailTemplateEnum::SPARK_PLUGS => $this->sparkPlugs,
            NomenclatureDetailTemplateEnum::WIPER_ADAPTER => $this->wiperAdapter,
            NomenclatureDetailTemplateEnum::AIR_FILTER => $this->airFilter,
            default => $this->generic,
        };

        return $strategy->calculate($type, $nomenclatures, $packDimensions);
    }
}
