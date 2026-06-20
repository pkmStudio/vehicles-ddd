<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Export\Services;

use App\Vehicles\Domain\Contracts\Application\Common\Services\DetailTemplateResolverInterface;
use App\Vehicles\Domain\Contracts\Application\Common\Services\WiperSpecificationServiceInterface;
use App\Vehicles\Domain\Contracts\Application\Export\Services\VehicleExportServiceInterface;
use App\Vehicles\Domain\Contracts\Application\Export\Support\ExportDetailsBuilderInterface;
use App\Vehicles\Domain\Contracts\Application\Export\Support\VehicleExportRowInterface;
use App\Vehicles\Domain\Contracts\Application\Export\Support\WiperRowExpanderInterface;
use App\Vehicles\Domain\DTOs\VehicleExportPlan;
use App\Vehicles\Domain\Contracts\Infrastructure\Repositories\VehicleRepositoryInterface;
use App\Vehicles\Domain\Enums\DetailTemplateEnum;
use App\Vehicles\Domain\Models\PartSpecification;
use App\Vehicles\Domain\Models\Vehicle;
use Illuminate\Support\Collection;

final readonly class VehicleExportService implements VehicleExportServiceInterface
{
    private array $templateConfig;

    private array $fieldHeadings;

    public function __construct(
        private VehicleRepositoryInterface $vehicles,
        private VehicleExportRowInterface $vehicleRow,
        private ExportDetailsBuilderInterface $exportDetails,
        private WiperRowExpanderInterface $expander,
        private WiperSpecificationServiceInterface $wiper,
        DetailTemplateResolverInterface $templates,
    ) {
        $this->templateConfig = $templates->resolve(DetailTemplateEnum::WIPER)->getArrayTemplate();
        $this->fieldHeadings = $this->exportDetails->extractHeadingsFromTemplate($this->templateConfig);
    }

    public function buildExportPlan(bool $isAllow = false, bool $withWipers = true): VehicleExportPlan
    {
        return $withWipers
            ? VehicleExportPlan::all($isAllow)
            : VehicleExportPlan::mainOnly($isAllow);
    }

    public function getMainRows(bool $isAllow): Collection
    {
        return $this->vehicles->forMainSheet($isAllow);
    }

    public function getMainHeadings(): array
    {
        return $this->vehicleRow->getBaseHeadings();
    }

    public function mapMainRow(Vehicle $row): array
    {
        return $this->vehicleRow->getBaseData($row);
    }

    public function getWiperRows(bool $isAllow): Collection
    {
        return $this->expander->expand($this->vehicles->forWiperSheet($isAllow));
    }

    public function getWiperHeadings(): array
    {
        $specHeadings = [
            'Значение характеристики',
            'Название шаблона',
            'Приписка к поколению',
            'Приписка к описанию',
        ];

        return array_merge($this->vehicleRow->getBaseHeadings(), $specHeadings, $this->fieldHeadings);
    }

    public function mapWiperRow(object $row): array
    {
        $baseData = $this->vehicleRow->getBaseData($row->vehicle);
        $frontSpec = $row->frontSpec;
        $backSpec = $row->backSpec;

        if ($frontSpec === null && $backSpec === null) {
            return array_merge(
                $baseData,
                array_fill(0, 4, null),
                array_fill(0, count($this->fieldHeadings), null),
            );
        }

        $frontData = $frontSpec ? $this->wiper->sideData((array) $frontSpec->details, WiperSpecificationServiceInterface::SIDE_FRONT) : [];
        $backData = $backSpec ? $this->wiper->sideData((array) $backSpec->details, WiperSpecificationServiceInterface::SIDE_BACK) : [];

        $specData = [
            $frontSpec?->featureValue?->name ?? $backSpec?->featureValue?->name,
            DetailTemplateEnum::WIPER->value,
            $frontSpec?->name ?? $backSpec?->name,
            $frontSpec?->text ?? $backSpec?->text,
        ];

        $merged = new PartSpecification;
        $merged->setAttribute('details', $this->wiper->mergeForExport($frontData, $backData));
        $detailsData = $this->exportDetails->getDetailsData($merged, $this->templateConfig);

        return array_merge($baseData, $specData, $detailsData);
    }
}
