<?php

declare(strict_types=1);

namespace App\Warehouse\Export\Infrastructure\Repositories;

use App\Warehouse\Export\Domain\Contracts\Repositories\TypeRepositoryInterface;
use App\Warehouse\Export\Domain\ModelData\TypeData;
use App\Warehouse\Export\Infrastructure\Models\Type;
use Illuminate\Support\Collection;

/**
 * Читает типы Warehouse-номенклатуры для экспорта и справочного листа.
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

    /**
     * Возвращает один тип по id или null, если он отсутствует.
     */
    public function find(int $id): ?TypeData
    {
        $type = Type::query()->find($id);

        return $type === null ? null : TypeData::from($type);
    }
}
