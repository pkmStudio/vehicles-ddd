<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\Contracts\Clients;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;

/**
 * Локальный read-only client Templates shared-kernel для Vehicles Export.
 */
interface TemplatesClientInterface
{
    /**
     * Возвращает заголовки vehicle details-шаблона.
     *
     * Шаги:
     * 1) Передать template identifier в Templates shared-kernel.
     * 2) Вернуть headings в порядке Excel-колонок.
     *
     * @return array<int, string>
     */
    public function vehicleDetailHeadings(DetailTemplateEnum $template): array;

    /**
     * Возвращает справочные значения vehicle details-шаблона.
     *
     * Шаги:
     * 1) Передать template identifier в Templates shared-kernel.
     * 2) Вернуть reference options, сгруппированные по колонкам.
     *
     * @return array<string, list<string>>
     */
    public function vehicleReferenceOptions(DetailTemplateEnum $template): array;

    /**
     * Рендерит сохраненный details-массив в Excel cells.
     *
     * Шаги:
     * 1) Передать template identifier и details в Templates renderer.
     * 2) Вернуть cells в порядке headings выбранного шаблона.
     *
     * @param  array<string, mixed>  $details
     * @return array<int, mixed>
     */
    public function renderVehicleDetails(DetailTemplateEnum $template, array $details): array;

    /**
     * Возвращает side-specific details дворников.
     *
     * Шаги:
     * 1) Передать details и side в Templates presenter.
     * 2) Вернуть normalized details выбранной стороны дворника.
     *
     * @param  array<string, mixed>  $details
     * @return array<string, mixed>
     */
    public function vehicleWiperSideData(array $details, string $side): array;

    /**
     * Объединяет front/back details дворников в export shape.
     *
     * Шаги:
     * 1) Передать front/back details в Templates presenter.
     * 2) Вернуть единый export shape legacy-листа дворников.
     *
     * @param  array<string, mixed>  $front
     * @param  array<string, mixed>  $back
     * @return array<string, mixed>
     */
    public function mergeVehicleWiperForExport(array $front, array $back): array;
}
