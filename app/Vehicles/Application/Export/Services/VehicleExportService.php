<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Export\Services;

use App\Vehicles\Application\Common\DetailTemplateResolver;
use App\Vehicles\Application\Export\Support\ExportDetailsBuilder;
use App\Vehicles\Application\Export\Support\VehicleExportRow;
use App\Vehicles\Application\Export\Support\WiperRowExpander;
use App\Vehicles\Domain\Contracts\Infrastructure\Repositories\VehicleRepositoryInterface;
use App\Vehicles\Domain\Enums\DetailTemplateEnum;
use App\Vehicles\Domain\Models\PartSpecification;
use App\Vehicles\Domain\Models\Vehicle;
use App\Vehicles\Domain\Services\WiperSpecificationService;
use Illuminate\Support\Collection;

final readonly class VehicleExportService
{
    private array $templateConfig;

    private array $fieldHeadings;

    public function __construct(
        private VehicleRepositoryInterface $vehicles,
        private VehicleExportRow $vehicleRow,
        private ExportDetailsBuilder $exportDetails,
        private WiperRowExpander $expander,
        private WiperSpecificationService $wiper,
        DetailTemplateResolver $templates,
    ) {
        $this->templateConfig = $templates->resolve(DetailTemplateEnum::WIPER)->getArrayTemplate();
        $this->fieldHeadings = $this->exportDetails->extractHeadingsFromTemplate($this->templateConfig);
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

        $frontData = $frontSpec ? $this->wiper->sideData((array) $frontSpec->details, WiperSpecificationService::SIDE_FRONT) : [];
        $backData = $backSpec ? $this->wiper->sideData((array) $backSpec->details, WiperSpecificationService::SIDE_BACK) : [];

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
