<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Clients;

use App\Modules\Templates\Domain\Contracts\Clients\TemplatesClientInterface as TemplatesPublicClientInterface;
use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Clients\TemplatesClientInterface;

/**
 * Anti-corruption client к Templates public API для данных details в выгрузке Vehicles.
 */
final readonly class TemplatesClient implements TemplatesClientInterface
{
    /**
     * Получить публичный client модуля Templates.
     *
     * Шаги:
     * - Принять внешний Templates contract через DI.
     * - Сохранить его для делегирования операций по details-шаблонам.
     */
    public function __construct(
        private TemplatesPublicClientInterface $templates,
    ) {}

    /**
     * Вернуть заголовки detail-шаблона автомобиля.
     *
     * Шаги:
     * - Преобразовать enum шаблона в публичное строковое значение.
     * - Делегировать построение headings модулю Templates.
     *
     * @return list<string>
     */
    public function vehicleDetailHeadings(DetailTemplateEnum $template): array
    {
        return $this->templates->vehicleDetailHeadings($template->value);
    }

    /**
     * Вернуть справочные варианты значений для detail-шаблона автомобиля.
     *
     * Шаги:
     * - Преобразовать enum шаблона в публичное строковое значение.
     * - Делегировать получение reference options модулю Templates.
     *
     * @return array<string, array<int, string>>
     */
    public function vehicleReferenceOptions(DetailTemplateEnum $template): array
    {
        return $this->templates->vehicleReferenceOptions($template->value);
    }

    /**
     * Отрендерить details автомобиля в export-строку.
     *
     * Шаги:
     * - Преобразовать enum шаблона в публичное строковое значение.
     * - Передать raw details в Templates renderer.
     * - Вернуть плоские значения ячеек для export sheet.
     *
     * @param  array<string, mixed>  $details
     * @return list<string>
     */
    public function renderVehicleDetails(DetailTemplateEnum $template, array $details): array
    {
        return $this->templates->renderVehicleDetails($template->value, $details);
    }

    /**
     * Вернуть данные одной стороны wiper specification.
     *
     * Шаги:
     * - Передать details и код стороны в Templates presenter.
     * - Вернуть нормализованные данные стороны для строки экспорта.
     *
     * @param  array<string, mixed>  $details
     * @return array<string, mixed>
     */
    public function vehicleWiperSideData(array $details, string $side): array
    {
        return $this->templates->vehicleWiperSideData($details, $side);
    }

    /**
     * Объединить front/back wiper details в формат export presentation.
     *
     * Шаги:
     * - Передать данные переднего и заднего дворника в Templates presenter.
     * - Вернуть объединенные значения для единой строки выгрузки.
     *
     * @param  array<string, mixed>  $front
     * @param  array<string, mixed>  $back
     * @return array<string, mixed>
     */
    public function mergeVehicleWiperForExport(array $front, array $back): array
    {
        return $this->templates->mergeVehicleWiperForExport($front, $back);
    }
}
