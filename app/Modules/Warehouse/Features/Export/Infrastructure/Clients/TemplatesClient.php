<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Infrastructure\Clients;

use App\Modules\Templates\Domain\Contracts\Clients\TemplatesClientInterface as TemplatesPublicClientInterface;
use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Clients\TemplatesClientInterface;

/**
 * Адаптер Export-фичи к публичному Templates client.
 */
final readonly class TemplatesClient implements TemplatesClientInterface
{
    /**
     * Получает публичный client Templates shared-kernel.
     *
     * Шаги:
     * 1) Принять внешний для фичи client шаблонов.
     * 2) Сохранить его за локальным Export-портом.
     */
    public function __construct(
        private TemplatesPublicClientInterface $templates,
    ) {}

    /**
     * Возвращает detail-заголовки номенклатуры по template enum.
     *
     * Шаги:
     * 1) Получить типизированный enum шаблона номенклатуры.
     * 2) Передать строковое значение в публичный Templates client.
     * 3) Вернуть заголовки без изменения порядка.
     *
     * @return array<int, string>
     */
    public function nomenclatureDetailHeadings(NomenclatureDetailTemplateEnum $template): array
    {
        return $this->templates->nomenclatureDetailHeadings($template->value);
    }

    /**
     * Возвращает справочные значения для detail-шаблона номенклатуры.
     *
     * Шаги:
     * 1) Получить enum шаблона от Export-сервиса.
     * 2) Передать value в Templates client.
     * 3) Вернуть сгруппированные справочники для reference-листа.
     *
     * @return array<string, list<string>>
     */
    public function nomenclatureReferenceOptions(NomenclatureDetailTemplateEnum $template): array
    {
        return $this->templates->nomenclatureReferenceOptions($template->value);
    }

    /**
     * Рендерит details номенклатуры в плоские Excel-значения.
     *
     * Шаги:
     * 1) Получить enum шаблона и raw details из Warehouse-номенклатуры.
     * 2) Передать строковый код шаблона и details в Templates client.
     * 3) Вернуть значения в порядке detail-заголовков.
     *
     * @param  array<string, mixed>  $details
     * @return array<int, mixed>
     */
    public function renderNomenclatureDetails(NomenclatureDetailTemplateEnum $template, array $details): array
    {
        return $this->templates->renderNomenclatureDetails($template->value, $details);
    }
}
