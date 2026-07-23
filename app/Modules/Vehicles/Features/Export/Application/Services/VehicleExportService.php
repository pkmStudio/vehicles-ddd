<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Application\Services;

use App\Modules\Vehicles\Features\Export\Domain\Contracts\Clients\TemplatesClientInterface;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Services\VehicleExportServiceInterface;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Services\Rows\VehicleExportRowInterface;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Services\Expanders\WiperRowExpanderInterface;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Export\Domain\DTOs\WiperExportRowDTO;
use App\Modules\Vehicles\Features\Export\Domain\ModelData\VehicleData;
use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Templates\Domain\Enums\Wiper\WiperSideEnum;
use Illuminate\Support\Collection;

/**
 * Готовит строки и заголовки Excel-экспорта автомобилей.
 */
final readonly class VehicleExportService implements VehicleExportServiceInterface
{
    private array $fieldHeadings;

    /**
     * Инициализирует зависимости и заголовки шаблона дворников.
     */
    public function __construct(
        private VehicleRepositoryInterface $vehicles,
        private VehicleExportRowInterface $vehicleRow,
        private WiperRowExpanderInterface $expander,
        private TemplatesClientInterface $templates,
    ) {
        $this->fieldHeadings = $this->templates->vehicleDetailHeadings(DetailTemplateEnum::WIPER);
    }

    /**
     * Возвращает строки основного листа автомобилей.
     */
    public function getMainRows(bool $isAllow): Collection
    {
        return $this->vehicles->forMainSheet($isAllow);
    }

    /**
     * Возвращает заголовки основного листа автомобилей.
     */
    public function getMainHeadings(): array
    {
        return $this->vehicleRow->getBaseHeadings();
    }

    /**
     * Преобразует Data-снимок автомобиля в строку основного листа.
     */
    public function mapMainRow(VehicleData $row): array
    {
        return $this->vehicleRow->getBaseData($row);
    }

    /**
     * Возвращает развернутые строки листа дворников.
     */
    public function getWiperRows(bool $isAllow): Collection
    {
        return $this->expander->expand($this->vehicles->forWiperSheet($isAllow));
    }

    /**
     * Возвращает заголовки листа дворников.
     */
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

    /**
     * Преобразует пару спецификаций дворников в плоский набор Excel-ячеек.
     */
    public function mapWiperRow(WiperExportRowDTO $row): array
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

        $frontData = $frontSpec ? $this->templates->vehicleWiperSideData($frontSpec->details, WiperSideEnum::FRONT->value) : [];
        $backData = $backSpec ? $this->templates->vehicleWiperSideData($backSpec->details, WiperSideEnum::BACK->value) : [];

        $specData = [
            $frontSpec?->featureValue?->name ?? $backSpec?->featureValue?->name,
            DetailTemplateEnum::WIPER->value,
            $frontSpec?->name ?? $backSpec?->name,
            $frontSpec?->text ?? $backSpec?->text,
        ];

        $detailsData = $this->templates->renderVehicleDetails(
            DetailTemplateEnum::WIPER,
            $this->templates->mergeVehicleWiperForExport($frontData, $backData),
        );

        return array_merge($baseData, $specData, $detailsData);
    }
}
