<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Application\Services;

use App\Modules\Vehicles\Features\Export\Domain\Contracts\Services\EngineExportServiceInterface;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Services\Rows\EngineExportRowInterface;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Services\Expanders\PartSpecificationRowExpanderInterface;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Clients\TemplatesClientInterface;
use App\Modules\Vehicles\Features\Export\Domain\DTOs\PartSpecificationExportRowDTO;
use App\Modules\Vehicles\Features\Export\Domain\ModelData\EngineData;
use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use Illuminate\Support\Collection;

final readonly class EngineExportService implements EngineExportServiceInterface
{
    private array $fieldHeadings;

    public function __construct(
        private EngineRepositoryInterface $engines,
        private EngineExportRowInterface $engineRow,
        private PartSpecificationRowExpanderInterface $expander,
        private TemplatesClientInterface $templates,
    ) {
        $this->fieldHeadings = $this->templates->vehicleDetailHeadings(DetailTemplateEnum::SPARK_PLUGS);
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
            $detailsData = $this->templates->renderVehicleDetails(DetailTemplateEnum::SPARK_PLUGS, $row->specification->details);
        } else {
            $detailsData = array_fill(0, count($this->fieldHeadings), null);
        }

        return array_merge($baseData, $detailsData);
    }
}
