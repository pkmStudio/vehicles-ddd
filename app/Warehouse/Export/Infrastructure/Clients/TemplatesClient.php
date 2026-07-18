<?php

declare(strict_types=1);

namespace App\Warehouse\Export\Infrastructure\Clients;

use App\Templates\Domain\Contracts\Clients\TemplatesClientInterface as TemplatesPublicClientInterface;
use App\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Warehouse\Export\Domain\Contracts\Clients\TemplatesClientInterface;

final readonly class TemplatesClient implements TemplatesClientInterface
{
    public function __construct(
        private TemplatesPublicClientInterface $templates,
    ) {}

    public function nomenclatureDetailHeadings(NomenclatureDetailTemplateEnum $template): array
    {
        return $this->templates->nomenclatureDetailHeadings($template->value);
    }

    public function nomenclatureReferenceOptions(NomenclatureDetailTemplateEnum $template): array
    {
        return $this->templates->nomenclatureReferenceOptions($template->value);
    }

    public function renderNomenclatureDetails(NomenclatureDetailTemplateEnum $template, array $details): array
    {
        return $this->templates->renderNomenclatureDetails($template->value, $details);
    }
}
