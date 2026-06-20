<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Export\Services;

use App\Vehicles\Application\Common\DetailTemplateResolver;
use App\Vehicles\Application\Export\Support\EngineExportRow;
use App\Vehicles\Application\Export\Support\ExportDetailsBuilder;
use App\Vehicles\Application\Export\Support\PartSpecificationRowExpander;
use App\Vehicles\Domain\Contracts\Infrastructure\Repositories\EngineRepositoryInterface;
use App\Vehicles\Domain\Enums\DetailTemplateEnum;
use App\Vehicles\Domain\Models\Engine;
use Illuminate\Support\Collection;

final readonly class EngineExportService
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

    public function getMainRows(): Collection
    {
        return $this->engines->all();
    }

    public function getMainHeadings(): array
    {
        return $this->engineRow->getBaseHeadings();
    }

    public function mapMainRow(Engine $row): array
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
            $detailsData = $this->exportDetails->getDetailsData($row->specification, $this->templateConfig);
        } else {
            $detailsData = array_fill(0, count($this->fieldHeadings), null);
        }

        return array_merge($baseData, $detailsData);
    }
}
