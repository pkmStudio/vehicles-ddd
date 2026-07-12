<?php

declare(strict_types=1);

namespace App\Warehouse\Import\Domain\Contracts\Repositories;

use App\Warehouse\Import\Domain\ModelData\BrandData;
use Illuminate\Support\Collection;

/**
 * Порт чтения брендов Warehouse для резолва brand_id при импорте номенклатуры.
 */
interface BrandRepositoryInterface
{
    /**
     * Возвращает все бренды.
     *
     * @return Collection<int, BrandData>
     */
    public function all(): Collection;
}
