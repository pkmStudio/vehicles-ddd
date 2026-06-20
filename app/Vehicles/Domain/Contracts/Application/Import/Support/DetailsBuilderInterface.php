<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Application\Import\Support;

interface DetailsBuilderInterface
{
    /**
     * @param  array  $row
     * @param  int  $startIndex
     * @param  array  $template
     * @return array
     */
    public function buildDetails(array $row, int &$startIndex, array $template): array;
}

