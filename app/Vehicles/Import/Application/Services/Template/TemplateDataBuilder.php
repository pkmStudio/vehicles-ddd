<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Application\Services\Template;

use App\Vehicles\Templates\Domain\Contracts\DetailTemplateResolverInterface;
use App\Vehicles\Import\Domain\Contracts\Services\Template\DetailsBuilderInterface;
use App\Vehicles\Import\Domain\Contracts\Services\Template\TemplateDataBuilderInterface;
use App\Vehicles\Templates\Domain\Enums\DetailTemplateEnum;

final readonly class TemplateDataBuilder implements TemplateDataBuilderInterface
{
    public function __construct(
        private DetailsBuilderInterface $detailsBuilder,
        private DetailTemplateResolverInterface $templates,
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
