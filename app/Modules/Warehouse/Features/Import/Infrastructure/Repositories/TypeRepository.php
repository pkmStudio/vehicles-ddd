<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Infrastructure\Repositories;

use App\Modules\Warehouse\Features\Import\Domain\Contracts\Repositories\TypeRepositoryInterface;
use App\Modules\Warehouse\Features\Import\Domain\ModelData\TypeData;
use App\Modules\Warehouse\Features\Import\Infrastructure\Models\Type;
use Illuminate\Support\Collection;

/**
 * Читает типы Warehouse-номенклатуры для резолва type_id при импорте.
 */
final readonly class TypeRepository implements TypeRepositoryInterface
{
    /**
     * Возвращает все типы в стабильном порядке id.
     *
     * @return Collection<int, TypeData>
     */
    public function all(): Collection
    {
        return TypeData::collect(Type::query()->orderBy('id')->get(), Collection::class);
    }
}
