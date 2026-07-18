<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\Contracts\Repositories;

use App\Modules\Warehouse\Features\Import\Domain\ModelData\BrandData;
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
