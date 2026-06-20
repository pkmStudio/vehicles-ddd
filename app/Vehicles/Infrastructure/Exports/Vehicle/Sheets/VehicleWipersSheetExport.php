<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Exports\Vehicle\Sheets;

use App\Vehicles\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Vehicles\Domain\Enums\DetailTemplateEnum;
use App\Vehicles\Infrastructure\Exports\Support\ExportDetailsBuilder;
use App\Vehicles\Infrastructure\Exports\Support\PartSpecificationRowExpander;
use App\Vehicles\Infrastructure\Exports\Support\VehicleExportRow;
use App\Vehicles\Infrastructure\Support\DetailTemplateResolver;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

final readonly class VehicleWipersSheetExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    private array $templateConfig;

    private array $fieldHeadings;

    public function __construct(
        private VehicleRepositoryInterface $vehicles,
        private VehicleExportRow $vehicleRow,
        private ExportDetailsBuilder $exportDetails,
        private PartSpecificationRowExpander $expander,
        DetailTemplateResolver $templates,
        private bool $isAllow = false,
    ) {
        $this->templateConfig = $templates->resolve(DetailTemplateEnum::WIPER)->getArrayTemplate();
        $this->fieldHeadings = $this->exportDetails->extractHeadingsFromTemplate($this->templateConfig);
    }

    public function title(): string
    {
        return 'Дворники';
    }

    public function collection(): Collection
    {
        return $this->expander->expand($this->vehicles->forWiperSheet($this->isAllow));
    }

    /**
     * @throws \Exception
     */
    public function map($row): array
    {
        $baseData = $this->vehicleRow->getBaseData($row->entity);
        $specification = $row->specification;

        if ($specification) {
            $specData = [
                $specification->featureValue?->name,
                $specification->template?->value,
                $specification->name,
                $specification->text,
            ];

            $detailsData = $this->exportDetails->getDetailsData($specification, $this->templateConfig);
        } else {
            // 4 пустых столбца = ровно по числу $specHeadings в headings() (иначе колонки съезжают).
            $specData = array_fill(0, 4, null);
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
