<?php

declare(strict_types=1);

namespace App\Warehouse\Import\Infrastructure\Repositories;

use App\Warehouse\Import\Domain\Contracts\Repositories\TypeRepositoryInterface;
use App\Warehouse\Import\Domain\ModelData\TypeData;
use App\Warehouse\Import\Infrastructure\Models\Type;
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
