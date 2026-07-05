<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Services\Engine;

interface EngineEditableColumnsMapperInterface
{
    /**
     * @param  array<int, mixed>  $row
     * @return array<string, mixed>
     */
    public function extractEditableAttributes(array $row): array;
}
