<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Application\Services;

use App\Vehicles\Templates\Domain\Contracts\DetailTemplateResolverInterface;
use App\Vehicles\Export\Domain\Contracts\Services\EngineExportServiceInterface;
use App\Vehicles\Export\Domain\Contracts\Services\Rows\EngineExportRowInterface;
use App\Vehicles\Export\Domain\Contracts\Services\Details\ExportDetailsBuilderInterface;
use App\Vehicles\Export\Domain\Contracts\Services\Expanders\PartSpecificationRowExpanderInterface;
use App\Vehicles\Export\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Vehicles\Export\Domain\ModelData\Engine\EngineData;
use App\Vehicles\Templates\Domain\Enums\DetailTemplateEnum;
use Illuminate\Support\Collection;

final readonly class EngineExportService implements EngineExportServiceInterface
{
    private array $templateConfig;

    private array $fieldHeadings;

    public function __construct(
        private EngineRepositoryInterface $engines,
        private EngineExportRowInterface $engineRow,
        private ExportDetailsBuilderInterface $exportDetails,
        private PartSpecificationRowExpanderInterface $expander,
        DetailTemplateResolverInterface $templates,
    ) {
        $this->templateConfig = $templates->resolve(DetailTemplateEnum::SPARK_PLUGS)->getArrayTemplate();
        $this->fieldHeadings = $this->exportDetails->extractHeadingsFromTemplate($this->templateConfig);
    }

    public function getMainRows(): Collection
    {
        return $this->engines->all();
    }

    public function getMainHeadings(): array
    {
        return $this->engineRow->getBaseHeadings();
    }

    public function mapMainRow(EngineData $row): array
    {
        return $this->engineRow->getBaseData($row);
    }

    public function getSparkPlugRows(): Collection
    {
        return $this->expander->expand($this->engines->forSparkPlugSheet());
    }

    public function getSparkPlugHeadings(): array
    {
        return array_merge($this->engineRow->getBaseHeadings(), $this->fieldHeadings);
    }

    public function mapSparkPlugRow(object $row): array
    {
        $baseData = $this->engineRow->getBaseData($row->entity);

        if ($row->specification) {
            $detailsData = $this->exportDetails->getDetailsData($row->specification->details, $this->templateConfig);
        } else {
            $detailsData = array_fill(0, count($this->fieldHeadings), null);
        }

        return array_merge($baseData, $detailsData);
    }
}
