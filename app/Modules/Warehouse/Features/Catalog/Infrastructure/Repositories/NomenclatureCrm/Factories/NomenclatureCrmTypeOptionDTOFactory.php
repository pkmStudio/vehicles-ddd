<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories\NomenclatureCrm\Factories;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmOptionDTO;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories\NomenclatureCrm\NomenclatureCrmTypeTemplateResolver;

/**
 * Собирает CRM type option DTO из SQL-проекции типа номенклатуры.
 */
final readonly class NomenclatureCrmTypeOptionDTOFactory
{
    /**
     * Получает resolver details template для type option.
     *
     * Шаги:
     * 1) Принять NomenclatureCrmTypeTemplateResolver из DI container.
     * 2) Использовать resolver при заполнении meta.template.
     */
    public function __construct(
        private NomenclatureCrmTypeTemplateResolver $templateResolver,
    ) {}

    /**
     * Собирает option DTO типа номенклатуры для CRM фильтров.
     *
     * Шаги:
     * 1) Считать id, name и char из type projection.
     * 2) Разрешить details template по id/char типа.
     * 3) Вернуть NomenclatureCrmOptionDTO с meta.char и meta.template.
     */
    public function make(object $type): NomenclatureCrmOptionDTO
    {
        return new NomenclatureCrmOptionDTO(
            id: (int) $type->id,
            label: (string) $type->name,
            meta: [
                'char' => isset($type->char) ? (string) $type->char : null,
                'template' => $this->templateResolver->value($type),
            ],
        );
    }
}
