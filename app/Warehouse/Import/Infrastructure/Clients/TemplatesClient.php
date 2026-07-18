<?php

declare(strict_types=1);

namespace App\Warehouse\Import\Infrastructure\Clients;

use App\Templates\Domain\Contracts\Clients\TemplatesClientInterface as TemplatesPublicClientInterface;
use App\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Warehouse\Import\Domain\Contracts\Clients\TemplatesClientInterface;

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
