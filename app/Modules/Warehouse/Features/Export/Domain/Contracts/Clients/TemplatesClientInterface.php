<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Domain\Contracts\Clients;

use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;

interface TemplatesClientInterface
{
    /** @return array<int, string> */
    public function nomenclatureDetailHeadings(NomenclatureDetailTemplateEnum $template): array;

    /** @return array<string, list<string>> */
    public function nomenclatureReferenceOptions(NomenclatureDetailTemplateEnum $template): array;

    /**
     * @param  array<string, mixed>  $details
     * @return array<int, mixed>
     */
    public function renderNomenclatureDetails(NomenclatureDetailTemplateEnum $template, array $details): array;
}
