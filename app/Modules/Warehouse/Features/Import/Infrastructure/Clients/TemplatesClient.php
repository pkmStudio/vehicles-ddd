<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Infrastructure\Clients;

use App\Modules\Templates\Domain\Contracts\Clients\TemplatesClientInterface as TemplatesPublicClientInterface;
use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Clients\TemplatesClientInterface;

final readonly class TemplatesClient implements TemplatesClientInterface
{
    public function __construct(
        private TemplatesPublicClientInterface $templates,
    ) {}

    public function buildNomenclatureDetails(
        NomenclatureDetailTemplateEnum $template,
        array $row,
        int $startIndex,
    ): array {
        return $this->templates->buildNomenclatureDetails($template->value, $row, $startIndex);
    }
}
