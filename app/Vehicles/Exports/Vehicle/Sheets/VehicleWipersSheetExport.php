<?php

declare(strict_types=1);

namespace App\Vehicles\Exports\Vehicle\Sheets;

use App\Vehicles\Templates\Vehicle\VehicleTemplateFactory;
use App\Vehicles\Models\Vehicle;
use App\Vehicles\Traits\BuildExportDetails;
use App\Vehicles\Traits\HasVehicleBaseData;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

final readonly class VehicleWipersSheetExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    use BuildExportDetails;
    use HasVehicleBaseData;

    private bool $isAllow;

    private array $templateConfig;

    private array $fieldHeadings;

    private const string WIPER_TEMPLATE = 'wiper';

    public function __construct(bool $isAllow = false)
    {
        $this->isAllow = $isAllow;
        $this->loadTemplateConfig();
    }

    /**
     * Загружает конфигурацию шаблона для дворников
     * В случае ошибки инициализирует пустые массивы
     */
    private function loadTemplateConfig(): void
    {
        try {
            $this->templateConfig = VehicleTemplateFactory::make(self::WIPER_TEMPLATE)->getArrayTemplate();
            $this->fieldHeadings = $this->extractHeadingsFromTemplate($this->templateConfig);
        } catch (\Exception $e) {
            $this->templateConfig = [];
            $this->fieldHeadings = [];
        }
    }

    /**
     * Возвращает название листа в Excel файле
     */
    public function title(): string
    {
        return 'Дворники';
    }

    /**
     * Формирует коллекцию данных для экспорта
     * Для каждого автомобиля создает отдельные строки для каждой спецификации
     */
    public function collection(): Collection
    {
        $vehicles = Vehicle::query()
            ->with([
                'manufacturer',
                'parent',
                'partSpecifications' => function ($query) {
                    $query->whereHas('detailTemplate', function ($q) {
                        $q->where('template', self::WIPER_TEMPLATE);
                    })->with(['detailTemplate', 'featureValue']);
                },
            ]);

        if ($this->isAllow) {
            $vehicles->where('is_allow', true);
        }

        $vehicles = $vehicles->get();
        $expandedCollection = collect();

        foreach ($vehicles as $vehicle) {
            if ($vehicle->partSpecifications->isEmpty()) {
                $expandedCollection->push((object) [
                    'vehicle' => $vehicle,
                    'specification' => null,
                ]);
            } else {
                foreach ($vehicle->partSpecifications as $specification) {
                    $expandedCollection->push((object) [
                        'vehicle' => $vehicle,
                        'specification' => $specification,
                    ]);
                }
            }
        }

        return $expandedCollection;
    }

    /**
     * Преобразует строку данных в массив значений для Excel
     *
     * @param  object  $row  Объект с данными автомобиля и спецификации
     *
     * @throws \Exception
     */
    public function map($row): array
    {
        $vehicle = $row->vehicle;
        $specification = $row->specification;
        $baseData = $this->getBaseData($vehicle);

        if ($specification) {
            $specData = [
                $specification->featureValue?->name,
                $specification->detailTemplate->template,
                $specification->name,
                $specification->text,
            ];

            $detailsData = $this->getDetailsData($specification, $this->templateConfig);
        } else {
            $specData = array_fill(0, 6, null);
            $detailsData = array_fill(0, count($this->fieldHeadings), null);
        }

        return array_merge($baseData, $specData, $detailsData);
    }

    /**
     * Возвращает массив заголовков колонок для Excel
     * 1. Заголовки автомобиля
     * 2. Заголовки спецификации дворников
     */
    public function headings(): array
    {
        $headings = $this->getBaseHeadings();
        $specHeadings = [
            'Значение характеристики',
            'Название шаблона',
            'Приписка к поколению',
            'Приписка к описанию',
        ];

        return array_merge($headings, $specHeadings, $this->fieldHeadings);
    }
}
