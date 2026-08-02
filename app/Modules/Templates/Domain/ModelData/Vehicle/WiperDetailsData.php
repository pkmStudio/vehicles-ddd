<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\ModelData\Vehicle;

use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;

/**
 * Форма шаблона `wiper`. `position` (переключатель стороны в старой Filament-форме) сюда не
 * входит — это была чисто UI-конструкция, в details никогда не попадала.
 *
 * Сборка из Excel-строки — забота `Templates\Application\Factories\DetailsDataFactory`. Рендер в
 * Excel-ячейки экспорта — забота `Templates\Application\Services\DetailsDataPresenter`. Сам
 * класс — чистый объект-значение, ни то ни другое поведение сюда не входит.
 *
 * Excel-лист всегда содержит все 10 колонок подряд, но стороны независимы: полностью пустая
 * сторона пропускается, а частично заполненная валидируется как обычная заполненная сторона.
 * Разбор по фактически заполненной стороне — забота `WiperSpecificationService`
 * (`splitDetails`/`hasUsableSideDetails`), она вызывается уже после сборки этого объекта.
 *
 * Экспорт, наоборот, читает уже смерженный (`WiperSpecificationService::mergeForExport`) массив,
 * где отсутствующая сторона либо есть, либо её ключа в массиве вообще нет — поэтому здесь
 * `front`/`back` nullable.
 */
final class WiperDetailsData extends AbstractDetailsData
{
    public function __construct(
        public readonly ?WiperFrontDetailsData $front = null,
        public readonly ?WiperBackDetailsData $back = null,
    ) {}
}
