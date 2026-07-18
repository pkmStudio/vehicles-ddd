<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\Contracts\Clients;

use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;

interface TemplatesClientInterface
{
    /**
     * @param  array<int, mixed>  $row
     * @return array<string, mixed>
     */
    public function buildNomenclatureDetails(
        NomenclatureDetailTemplateEnum $template,
        array $row,
        int $startIndex,
    ): array;
}
