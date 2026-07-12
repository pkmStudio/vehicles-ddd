<?php

declare(strict_types=1);

namespace App\Warehouse\Import\Infrastructure\Repositories;

use App\Warehouse\Import\Domain\Contracts\Repositories\BrandRepositoryInterface;
use App\Warehouse\Import\Domain\ModelData\BrandData;
use App\Warehouse\Import\Infrastructure\Models\Brand;
use Illuminate\Support\Collection;

/**
 * Читает бренды Warehouse для резолва brand_id при импорте номенклатуры.
 */
final readonly class BrandRepository implements BrandRepositoryInterface
{
    /**
     * Возвращает все бренды в стабильном порядке id.
     *
     * @return Collection<int, BrandData>
     */
    public function all(): Collection
    {
        return BrandData::collect(Brand::query()->orderBy('id')->get(), Collection::class);
    }
}
