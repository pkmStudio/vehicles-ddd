<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Exports\Vehicle\Sheets;

use App\Vehicles\Infrastructure\Repositories\Vehicle\VehicleRepositoryInterface;
use App\Vehicles\Domain\Templates\Vehicle\VehicleTemplateFactory;
use App\Vehicles\Infrastructure\Support\ExportDetailsBuilder;
use App\Vehicles\Infrastructure\Support\VehicleExportRow;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

final readonly class VehicleWipersSheetExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{

    private const string WIPER_TEMPLATE = 'wiper';

    private array $templateConfig;

    private array $fieldHeadings;

    public function __construct(
        private VehicleRepositoryInterface $vehicles,
        private VehicleExportRow $vehicleRow,
        private ExportDetailsBuilder $exportDetails,
        private bool $isAllow = false,
    ) {
        try {
            $this->templateConfig = VehicleTemplateFactory::make(self::WIPER_TEMPLATE)->getArrayTemplate();
            $this->fieldHeadings = $this->exportDetails->extractHeadingsFromTemplate($this->templateConfig);
        } catch (\Exception $e) {
            $this->templateConfig = [];
            $this->fieldHeadings = [];
        }
    }

    public function title(): string
    {
        return 'Дворники';
    }

    public function collection(): Collection
    {
        $vehicles = $this->vehicles->forWiperSheet($this->isAllow);
        $expandedCollection = collect();

        foreach ($vehicles as $vehicle) {
            if ($vehicle->partSpecifications->isEmpty()) {
                $expandedCollection->push((object) ['vehicle' => $vehicle, 'specification' => null]);
            } else {
                foreach ($vehicle->partSpecifications as $specification) {
                    $expandedCollection->push((object) ['vehicle' => $vehicle, 'specification' => $specification]);
                }
            }
        }

        return $expandedCollection;
    }

    /**
     * @throws \Exception
     */
    public function map($row): array
    {
        $vehicle = $row->vehicle;
        $specification = $row->specification;
        $baseData = $this->vehicleRow->getBaseData($vehicle);

        if ($specification) {
            $specData = [
                $specification->featureValue?->name,
                $specification->template?->value,
                $specification->name,
                $specification->text,
            ];

            $detailsData = $this->exportDetails->getDetailsData($specification, $this->templateConfig);
        } else {
            $specData = array_fill(0, 6, null);
            $detailsData = array_fill(0, count($this->fieldHeadings), null);
        }

        return array_merge($baseData, $specData, $detailsData);
    }

    public function headings(): array
    {
        $headings = $this->vehicleRow->getBaseHeadings();
        $specHeadings = [
            'Значение характеристики',
            'Название шаблона',
            'Приписка к поколению',
            'Приписка к описанию',
        ];

        return array_merge($headings, $specHeadings, $this->fieldHeadings);
    }
}
