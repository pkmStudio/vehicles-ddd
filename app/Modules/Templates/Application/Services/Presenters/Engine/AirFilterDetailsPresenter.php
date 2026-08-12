<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Services\Presenters\Engine;

use App\Modules\Templates\Application\Services\Presenters\AbstractDetailsPresenter;
use App\Modules\Templates\Application\Traits\FormatsExportCells;
use App\Modules\Templates\Domain\Enums\Filter\FormEnum;
use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;
use App\Modules\Templates\Domain\ModelData\Engine\AirFilterDetailsData;

/**
 * Рендерит форму `airFilter` в плоский набор Excel-ячеек экспорта (не подключена ни к одному
 * Import/Export сценарию — см. докблок `AirFilterDetailsData`, портируется без покрытия
 * тестами). Простой класс без собственного порта — вызывается только из `DetailsDataPresenter`.
 */
final readonly class AirFilterDetailsPresenter extends AbstractDetailsPresenter
{
    use FormatsExportCells;

    public function headings(): array
    {
        return ['Форма фильтра', 'Длина (мм)', 'Ширина (мм)', 'Высота (мм)', 'Диаметр (мм)'];
    }

    /** @return class-string<AirFilterDetailsData> */
    protected function dataClass(): string
    {
        return AirFilterDetailsData::class;
    }

    public function cells(AbstractDetailsData $data): array
    {
        $data = $this->ensureData($data, AirFilterDetailsData::class);

        return [
            $this->nameToLabelCell(FormEnum::class, $data->form),
            $this->floatArrayToString($data->length),
            $this->floatArrayToString($data->width),
            $this->floatArrayToString($data->height),
            $data->diameter,
        ];
    }
}
