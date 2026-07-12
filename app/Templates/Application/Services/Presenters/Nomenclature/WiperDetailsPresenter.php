<?php

declare(strict_types=1);

namespace App\Templates\Application\Services\Presenters\Nomenclature;

use App\Templates\Application\Traits\FormatsExportCells;
use App\Templates\Domain\Enums\PositionEnum;
use App\Templates\Domain\Enums\Wiper\ConstructionEnum;
use App\Templates\Domain\Enums\Wiper\FrontAdapterTypeEnum;
use App\Templates\Domain\Enums\Wiper\RearAdapterTypeEnum;
use App\Templates\Domain\Enums\Wiper\SeasonEnum;
use App\Templates\Domain\Enums\Wiper\SteeringCompatibilityEnum;
use App\Templates\Domain\ModelData\Nomenclature\WiperDetailsData;

/**
 * Рендерит форму `wiper` (Nomenclature) в плоский набор Excel-ячеек экспорта — характеристики
 * самого товара-щётки. Единственный шаблон Nomenclature без блока `metrics`.
 */
final readonly class WiperDetailsPresenter
{
    use FormatsExportCells;

    public function headings(): array
    {
        return [
            'Расположение', 'Конструкция', 'Сезонность',
            'Длина водительской щётки, мм', 'Длина пассажирской щётки, мм', 'Длина задней щётки, мм',
            'Тип крепления передних', 'Тип крепления задних',
            'Покрытие', 'Индикатор износа', 'Спойлер', 'Форсунка омывателя', 'Подогрев',
            'Сторона руля',
        ];
    }

    public function cells(WiperDetailsData $data): array
    {
        return [
            $this->nameToLabelCell(PositionEnum::class, $data->position),
            $this->nameToLabelCell(ConstructionEnum::class, $data->construction),
            $this->nameToLabelCell(SeasonEnum::class, $data->season),
            $data->lengthMain,
            $data->lengthSecond,
            $data->lengthRear,
            $this->namesToLabelString($data->adapterTypeFront, FrontAdapterTypeEnum::class),
            $this->namesToLabelString($data->adapterTypeRear, RearAdapterTypeEnum::class),
            $data->coating,
            $this->boolToLabelCell($data->wearSensor),
            $this->boolToLabelCell($data->spoiler),
            $this->boolToLabelCell($data->washerNozzle),
            $this->boolToLabelCell($data->heated),
            $this->nameToLabelCell(SteeringCompatibilityEnum::class, $data->steering),
        ];
    }
}
