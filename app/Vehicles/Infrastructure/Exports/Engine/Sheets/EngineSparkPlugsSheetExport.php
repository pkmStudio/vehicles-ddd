<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Exports\Engine\Sheets;

use App\Vehicles\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Vehicles\Domain\Enums\DetailTemplateEnum;
use App\Vehicles\Infrastructure\Exports\Support\EngineExportRow;
use App\Vehicles\Infrastructure\Exports\Support\ExportDetailsBuilder;
use App\Vehicles\Infrastructure\Exports\Support\PartSpecificationRowExpander;
use App\Vehicles\Infrastructure\Support\DetailTemplateResolver;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

final readonly class EngineSparkPlugsSheetExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    private array $templateConfig;

    private array $fieldHeadings;

    public function __construct(
        private EngineRepositoryInterface $engines,
        private EngineExportRow $engineRow,
        private ExportDetailsBuilder $exportDetails,
        private PartSpecificationRowExpander $expander,
        DetailTemplateResolver $templates,
    ) {
        $this->templateConfig = $templates->resolve(DetailTemplateEnum::SPARK_PLUGS)->getArrayTemplate();
        $this->fieldHeadings = $this->exportDetails->extractHeadingsFromTemplate($this->templateConfig);
    }

    public function title(): string
    {
        return 'Свечи зажигания';
    }

    public function collection(): Collection
    {
        return $this->expander->expand($this->engines->forSparkPlugSheet());
    }

    public function map($row): array
    {
        $baseData = $this->engineRow->getBaseData($row->entity);

        if ($row->specification) {
            $detailsData = $this->exportDetails->getDetailsData($row->specification, $this->templateConfig);
        } else {
            $detailsData = array_fill(0, count($this->fieldHeadings), null);
        }

        return array_merge($baseData, $detailsData);
    }

    public function headings(): array
    {
        return array_merge($this->engineRow->getBaseHeadings(), $this->fieldHeadings);
    }
}
