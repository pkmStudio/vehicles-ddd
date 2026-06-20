<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Import\Support;

use App\Vehicles\Application\Common\DetailTemplateResolver;
use App\Vehicles\Application\Import\Support\DetailsBuilder;
use App\Vehicles\Domain\Enums\DetailTemplateEnum;

final readonly class TemplateDataBuilder
{
    public function __construct(
        private DetailsBuilder $detailsBuilder,
        private DetailTemplateResolver $templates,
    ) {}

    public function buildByTemplate(array $row, int $startIndex, DetailTemplateEnum $template): array
    {
        $index = $startIndex;

        return $this->detailsBuilder->buildDetails(
            $row,
            $index,
            $this->templates->resolve($template)->getArrayTemplate(),
        );
    }

    public function buildBySlug(array $row, int $startIndex, string $slug): array
    {
        return $this->buildByTemplate($row, $startIndex, DetailTemplateEnum::from($slug));
    }
}
