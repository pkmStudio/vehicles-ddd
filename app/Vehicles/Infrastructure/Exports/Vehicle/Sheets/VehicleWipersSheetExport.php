<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Exports\Vehicle\Sheets;

use App\Vehicles\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Vehicles\Domain\Enums\DetailTemplateEnum;
use App\Vehicles\Domain\Models\PartSpecification;
use App\Vehicles\Domain\Services\WiperSpecificationService;
use App\Vehicles\Infrastructure\Exports\Support\ExportDetailsBuilder;
use App\Vehicles\Infrastructure\Exports\Support\VehicleExportRow;
use App\Vehicles\Infrastructure\Exports\Support\WiperRowExpander;
use App\Vehicles\Infrastructure\Support\DetailTemplateResolver;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Лист «дворники»: дворники хранятся по одной записи на сторону (front/back), а в excel
 * нужен старый объединённый формат. Expander собирает строки {frontSpec, backSpec},
 * map() склеивает стороны обратно в {front, back} через доменный сервис.
 */
final readonly class VehicleWipersSheetExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    private array $templateConfig;

    private array $fieldHeadings;

    public function __construct(
        private VehicleRepositoryInterface $vehicles,
        private VehicleExportRow $vehicleRow,
        private ExportDetailsBuilder $exportDetails,
        private WiperRowExpander $expander,
        private WiperSpecificationService $wiper,
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
        $baseData = $this->vehicleRow->getBaseData($row->vehicle);
        $frontSpec = $row->frontSpec;
        $backSpec = $row->backSpec;

        if ($frontSpec === null && $backSpec === null) {
            // 4 пустых столбца = ровно по числу $specHeadings в headings() (иначе колонки съезжают).
            return array_merge(
                $baseData,
                array_fill(0, 4, null),
                array_fill(0, count($this->fieldHeadings), null),
            );
        }

        $frontData = $frontSpec ? $this->wiper->sideData((array) $frontSpec->details, WiperSpecificationService::SIDE_FRONT) : [];
        $backData = $backSpec ? $this->wiper->sideData((array) $backSpec->details, WiperSpecificationService::SIDE_BACK) : [];

        $specData = [
            $frontSpec?->featureValue?->name ?? $backSpec?->featureValue?->name,
            DetailTemplateEnum::WIPER->value,
            $frontSpec?->name ?? $backSpec?->name,
            $frontSpec?->text ?? $backSpec?->text,
        ];

        $merged = new PartSpecification;
        $merged->setAttribute('details', $this->wiper->mergeForExport($frontData, $backData));
        $detailsData = $this->exportDetails->getDetailsData($merged, $this->templateConfig);

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
