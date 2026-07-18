<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Infrastructure\Repositories;

use App\Modules\Warehouse\Features\Import\Domain\Contracts\Repositories\BrandRepositoryInterface;
use App\Modules\Warehouse\Features\Import\Domain\ModelData\BrandData;
use App\Modules\Warehouse\Features\Import\Infrastructure\Models\Brand;
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
