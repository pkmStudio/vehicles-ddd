<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Domain\Contracts\Clients;

use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;

interface TemplatesClientInterface
{
    /**
     * Возвращает detail-заголовки номенклатуры из Templates.
     *
     * Шаги:
     * 1) Принять enum шаблона номенклатуры.
     * 2) Передать выбор шаблона на adapter boundary Templates.
     * 3) Вернуть плоский список заголовков.
     *
     * @return array<int, string>
     */
    public function nomenclatureDetailHeadings(NomenclatureDetailTemplateEnum $template): array;

    /**
     * Возвращает справочники для detail-шаблона номенклатуры.
     *
     * Шаги:
     * 1) Принять enum шаблона, выбранный по типу Warehouse.
     * 2) Получить reference options у Templates.
     * 3) Вернуть группы значений для справочного Excel-листа.
     *
     * @return array<string, list<string>>
     */
    public function nomenclatureReferenceOptions(NomenclatureDetailTemplateEnum $template): array;

    /**
     * Рендерит raw details номенклатуры в значения Excel.
     *
     * Шаги:
     * 1) Принять enum шаблона и массив details из Warehouse.
     * 2) Передать данные в Templates renderer.
     * 3) Вернуть значения в порядке detail-заголовков.
     *
     * @param  array<string, mixed>  $details
     * @return array<int, mixed>
     */
    public function renderNomenclatureDetails(NomenclatureDetailTemplateEnum $template, array $details): array;
}
