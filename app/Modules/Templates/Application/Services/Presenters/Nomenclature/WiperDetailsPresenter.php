<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Services\Presenters\Nomenclature;

use App\Modules\Templates\Application\Services\Presenters\AbstractDetailsPresenter;
use App\Modules\Templates\Application\Traits\FormatsExportCells;
use App\Modules\Templates\Domain\Enums\PositionEnum;
use App\Modules\Templates\Domain\Enums\Wiper\CategoryEnum;
use App\Modules\Templates\Domain\Enums\Wiper\ConstructionEnum;
use App\Modules\Templates\Domain\Enums\Wiper\FrontAdapterTypeEnum;
use App\Modules\Templates\Domain\Enums\Wiper\RearAdapterTypeEnum;
use App\Modules\Templates\Domain\Enums\Wiper\SeasonEnum;
use App\Modules\Templates\Domain\Enums\Wiper\SteeringCompatibilityEnum;
use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;
use App\Modules\Templates\Domain\ModelData\Nomenclature\WiperDetailsData;

/**
 * Рендерит форму `wiper` (Nomenclature) в плоский набор Excel-ячеек экспорта — характеристики
 * самого товара-щётки. Единственный шаблон Nomenclature без блока `metrics`.
 */
final readonly class WiperDetailsPresenter extends AbstractDetailsPresenter
{
    use FormatsExportCells;

    /**
     * Этот метод возвращает колонки nomenclature wiper шаблона.
     * Шаги:
     * 1) Перечисляет справочные характеристики щётки, длины и типы креплений.
     * 2) Добавляет покрытие, boolean-признаки и совместимость рулевого управления.
     */
    public function headings(): array
    {
        return [
            'Расположение', 'Категория', 'Конструкция', 'Сезон',
            'Длина водительской (мм)', 'Длина пассажирской (мм)', 'Длина задней (мм)',
            'Тип крепления передних', 'Тип крепления задней',
            'Покрытие', 'Датчик износа', 'Спойлер', 'Форсунка омывателя', 'C подогревом',
            'Рулевое управление',
        ];
    }

    /**
     * Этот метод указывает Data-класс nomenclature wiper presenter-а.
     * Шаги:
     * 1) Возвращает class-string `WiperDetailsData`.
     *
     * @return class-string<WiperDetailsData>
     */
    protected function dataClass(): string
    {
        return WiperDetailsData::class;
    }

    /**
     * Этот метод рендерит nomenclature wiper details в Excel-ячейки.
     * Шаги:
     * 1) Проверяет тип `WiperDetailsData`.
     * 2) Переводит enum-name и multi-select поля в Excel-labels.
     * 3) Выводит длины, покрытие и boolean-признаки в порядке заголовков.
     */
    public function cells(AbstractDetailsData $data): array
    {
        $data = $this->ensureData($data, WiperDetailsData::class);

        return [
            $this->nameToLabelCell(PositionEnum::class, $data->position),
            $this->nameToLabelCell(CategoryEnum::class, $data->category),
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
