<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Import\Support;

use App\Vehicles\Domain\Contracts\Application\Common\Services\DetailTemplateResolverInterface;
use App\Vehicles\Domain\Enums\DetailTemplateEnum;
use App\Vehicles\Domain\Contracts\Application\Import\Support\TemplateDataBuilderInterface;
use App\Vehicles\Domain\Contracts\Application\Import\Support\DetailsBuilderInterface;

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
