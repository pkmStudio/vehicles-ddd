<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Factories\Builders\Nomenclature;

use App\Modules\Templates\Application\Factories\DetailsRowCursor;
use App\Modules\Templates\Application\Traits\BuildsNomenclatureMetrics;
use App\Modules\Templates\Domain\ModelData\Nomenclature\CvJointDetailsData;

/**
 * Строит форму шаблона `cvJoint` (Nomenclature, ШРУС) из Excel-строки. Не подключена ни к одному
 * Import/Export сценарию — см. докблок `CvJointDetailsData`.
 */
final readonly class CvJointDetailsBuilder
{
    use BuildsNomenclatureMetrics;

    /**
     * Этот метод собирает details ШРУС из Excel-строки.
     * Шаги:
     * 1) Читает резьбу, две длины, ABS, диаметр уплотнения и количество шлицов.
     * 2) Читает общий блок габаритов номенклатуры.
     * 3) Возвращает `CvJointDetailsData` с сохранением исходных типов полей.
     */
    public function build(DetailsRowCursor $cursor): CvJointDetailsData
    {
        return new CvJointDetailsData(
            thread1: $cursor->pullRequiredStringCell('Резьба 1'),
            length1: $cursor->pullRequiredFloatCell('Длина 1'),
            length2: $cursor->pullRequiredFloatCell('Длина 2'),
            abs: $cursor->pullRequiredStringCell('ABS'),
            sealDiameter: $cursor->pullRequiredFloatCell('Диаметр уплотнения'),
            splinesOuter: $cursor->pullRequiredIntCell('Наружные шлицы'),
            splinesInner: $cursor->pullRequiredIntCell('Внутренние шлицы'),
            metrics: $this->buildMetrics($cursor),
        );
    }
}
