<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Application\Import\Support;

interface EngineEditableColumnsMapperInterface
{
    /**
     * @param  array<int, mixed>  $row
     * @return array<string, mixed>
     */
    public function extractEditableAttributes(array $row): array;
}
