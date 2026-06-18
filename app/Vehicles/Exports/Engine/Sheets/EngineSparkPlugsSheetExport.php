<?php

declare(strict_types=1);

namespace App\Vehicles\Exports\Engine\Sheets;

use App\Vehicles\Repositories\Engine\EngineRepositoryInterface;
use App\Vehicles\Templates\Engine\EngineTemplateFactory;
use App\Vehicles\Traits\BuildExportDetails;
use App\Vehicles\Traits\HasEngineBaseData;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

final readonly class EngineSparkPlugsSheetExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    use BuildExportDetails;
    use HasEngineBaseData;

    private const string SPARK_PLUG_TEMPLATE = 'sparkPlugs';

    private array $templateConfig;

    private array $fieldHeadings;

    public function __construct(
        private EngineRepositoryInterface $engines,
    ) {
        try {
            $this->templateConfig = EngineTemplateFactory::make(self::SPARK_PLUG_TEMPLATE)->getArrayTemplate();
            $this->fieldHeadings = $this->extractHeadingsFromTemplate($this->templateConfig);
        } catch (\Exception $e) {
            $this->templateConfig = [];
            $this->fieldHeadings = [];
        }
    }

    public function title(): string
    {
        return 'Свечи зажигания';
    }

    public function collection(): Collection
    {
        $engines = $this->engines->forSparkPlugSheet();
        $expandedCollection = collect();

        foreach ($engines as $engine) {
            if ($engine->partSpecifications->isEmpty()) {
                $expandedCollection->push((object) ['engine' => $engine, 'specification' => null]);
            } else {
                foreach ($engine->partSpecifications as $specification) {
                    $expandedCollection->push((object) ['engine' => $engine, 'specification' => $specification]);
                }
            }
        }

        return $expandedCollection;
    }

    public function map($row): array
    {
        $baseData = $this->getBaseData($row->engine);

        if ($row->specification) {
            $detailsData = $this->getDetailsData($row->specification, $this->templateConfig);
        } else {
            $detailsData = array_fill(0, count($this->fieldHeadings), null);
        }

        return array_merge($baseData, $detailsData);
    }

    public function headings(): array
    {
        return array_merge($this->getBaseHeadings(), $this->fieldHeadings);
    }
}
