<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Application\Import\Support;

use App\Vehicles\Domain\Enums\Templates\DetailTemplateEnum;

interface TemplateDataBuilderInterface
{
    public function buildByTemplate(array $row, int $startIndex, DetailTemplateEnum $template): array;

    public function buildBySlug(array $row, int $startIndex, string $slug): array;
}

