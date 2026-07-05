<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Services\Template;

interface DetailsBuilderInterface
{
    public function buildDetails(array $row, int &$startIndex, array $template): array;
}
