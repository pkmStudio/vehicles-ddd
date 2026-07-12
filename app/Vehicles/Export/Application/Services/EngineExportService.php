<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Application\Services;

use App\Vehicles\Export\Domain\Contracts\Services\EngineExportServiceInterface;
use App\Vehicles\Export\Domain\Contracts\Services\Rows\EngineExportRowInterface;
use App\Vehicles\Export\Domain\Contracts\Services\Expanders\PartSpecificationRowExpanderInterface;
use App\Vehicles\Export\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Vehicles\Export\Domain\DTOs\PartSpecificationExportRowDTO;
use App\Vehicles\Export\Domain\ModelData\EngineData;
use App\Vehicles\Templates\Domain\Contracts\Services\DetailsDataPresenterInterface;
use App\Vehicles\Templates\Domain\Enums\DetailTemplateEnum;
use Illuminate\Support\Collection;

final readonly class EngineExportService implements EngineExportServiceInterface
{
    private array $fieldHeadings;

    public function __construct(
        private EngineRepositoryInterface $engines,
        private EngineExportRowInterface $engineRow,
        private PartSpecificationRowExpanderInterface $expander,
        private DetailsDataPresenterInterface $detailsPresenter,
    ) {
        $this->fieldHeadings = $this->detailsPresenter->headingsFor(DetailTemplateEnum::SPARK_PLUGS);
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

    public function mapSparkPlugRow(PartSpecificationExportRowDTO $row): array
    {
        $baseData = $this->engineRow->getBaseData($row->entity);

        if ($row->specification) {
            $detailsData = $this->detailsPresenter->toExportCells(DetailTemplateEnum::SPARK_PLUGS, $row->specification->details);
        } else {
            $detailsData = array_fill(0, count($this->fieldHeadings), null);
        }

        return array_merge($baseData, $detailsData);
    }
}
