<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\Contracts\Clients;

use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;

interface TemplatesClientInterface
{
    /**
     * Собирает details номенклатуры по шаблону Templates.
     *
     * Шаги:
     * 1) Принять тип шаблона, строку Excel и индекс первой колонки details.
     * 2) Передать сборку в shared-kernel Templates через adapter.
     * 3) Вернуть массив details, готовый для записи в Warehouse-номенклатуру.
     *
     * @param  array<int, mixed>  $row
     * @return array<string, mixed>
     */
    public function buildNomenclatureDetails(
        NomenclatureDetailTemplateEnum $template,
        array $row,
        int $startIndex,
    ): array;
}
