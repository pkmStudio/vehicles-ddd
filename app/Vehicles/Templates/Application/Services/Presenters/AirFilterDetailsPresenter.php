<?php

declare(strict_types=1);

namespace App\Vehicles\Templates\Application\Services\Presenters;

use App\Vehicles\Templates\Application\Traits\FormatsExportCells;
use App\Vehicles\Templates\Domain\Enums\Filter\FormEnum;
use App\Vehicles\Templates\Domain\ModelData\Engine\AirFilterDetailsData;

/**
 * Рендерит форму `airFilter` в плоский набор Excel-ячеек экспорта (не подключена ни к одному
 * Import/Export сценарию — см. докблок `AirFilterDetailsData`, портируется без покрытия
 * тестами). Простой класс без собственного порта — вызывается только из `DetailsDataPresenter`.
 */
final readonly class AirFilterDetailsPresenter
{
    use FormatsExportCells;

    public function headings(): array
    {
        return ['Форма фильтра', 'Длина (мм)', 'Ширина (мм)', 'Высота (мм)', 'Диаметр (мм)'];
    }

    public function cells(AirFilterDetailsData $data): array
    {
        return [
            $this->nameToLabelCell(FormEnum::class, $data->form),
            $this->floatArrayToString($data->length),
            $this->floatArrayToString($data->width),
            $this->floatArrayToString($data->height),
            $data->diameter,
        ];
    }
}
