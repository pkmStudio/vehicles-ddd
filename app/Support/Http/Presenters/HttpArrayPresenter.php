<?php

declare(strict_types=1);

namespace App\Support\Http\Presenters;

use App\Support\Http\Contracts\HttpArraySerializableInterface;
use Illuminate\Support\Collection;

final readonly class HttpArrayPresenter
{
    /**
     * @param  Collection<int, HttpArraySerializableInterface>  $items
     * @return list<array<string, mixed>>
     *
     * Шаги:
     * - Преобразовать каждый serializable DTO через item().
     * - Сбросить ключи collection для стабильного JSON list.
     * - Вернуть plain array для HTTP response.
     */
    public function collection(Collection $items): array
    {
        return $items
            ->map(fn (HttpArraySerializableInterface $item): array => $this->item($item))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     *
     * Шаги:
     * - Принять объект с явным HTTP serialization contract.
     * - Вернуть plain array для JSON response.
     */
    public function item(HttpArraySerializableInterface $item): array
    {
        return $item->toArray();
    }

    /**
     * @param  Collection<int, HttpArraySerializableInterface>  $data
     * @return array{data: list<array<string, mixed>>, meta: array<string, mixed>}
     *
     * Шаги:
     * - Сериализовать коллекцию элементов в поле data.
     * - Сериализовать metadata DTO тем же item() contract.
     * - Собрать стандартную envelope-структуру paginated response.
     */
    public function page(Collection $data, HttpArraySerializableInterface $meta): array
    {
        return [
            'data' => $this->collection($data),
            'meta' => $this->item($meta),
        ];
    }
}
