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

/**
 * Готовит строки и заголовки Excel-экспорта двигателей.
 */
final readonly class EngineExportService implements EngineExportServiceInterface
{
    private array $fieldHeadings;

    /**
     * Инициализирует зависимости и заголовки шаблона свечей зажигания.
     */
    public function __construct(
        private EngineRepositoryInterface $engines,
        private EngineExportRowInterface $engineRow,
        private PartSpecificationRowExpanderInterface $expander,
        private TemplatesClientInterface $templates,
    ) {
        $this->fieldHeadings = $this->templates->vehicleDetailHeadings(DetailTemplateEnum::SPARK_PLUGS);
    }

    /**
     * Возвращает строки основного листа двигателей.
     */
    public function getMainRows(): Collection
    {
        return $this->engines->all();
    }

    /**
     * Возвращает заголовки основного листа двигателей.
     */
    public function getMainHeadings(): array
    {
        return $this->engineRow->getBaseHeadings();
    }

    /**
     * Преобразует Data-снимок двигателя в строку основного листа.
     */
    public function mapMainRow(EngineData $row): array
    {
        return $this->engineRow->getBaseData($row);
    }

    /**
     * Возвращает развернутые строки листа свечей зажигания.
     */
    public function getSparkPlugRows(): Collection
    {
        return $this->expander->expand($this->engines->forSparkPlugSheet());
    }

    /**
     * Возвращает заголовки листа свечей зажигания.
     */
    public function getSparkPlugHeadings(): array
    {
        return array_merge($this->engineRow->getBaseHeadings(), $this->fieldHeadings);
    }

    /**
     * Преобразует строку спецификации свечей в плоский набор Excel-ячеек.
     */
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
