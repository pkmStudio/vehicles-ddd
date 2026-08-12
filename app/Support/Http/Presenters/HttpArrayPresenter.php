<?php

declare(strict_types=1);

namespace App\Support\Http\Presenters;

use Illuminate\Support\Collection;
use InvalidArgumentException;

final readonly class HttpArrayPresenter
{
    /**
     * @param  Collection<int, object>  $items
     * @return list<array<string, mixed>>
     *
     * Шаги:
     * - Преобразовать каждый DTO-like object через item().
     * - Сбросить ключи collection для стабильного JSON list.
     * - Вернуть plain array для HTTP response.
     */
    public function collection(Collection $items): array
    {
        return $items
            ->map(fn (object $item): array => $this->item($item))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     *
     * Шаги:
     * - Проверить, что объект умеет сериализоваться через toArray().
     * - Вызвать toArray() и убедиться, что результат действительно array.
     * - Вернуть массив или выбросить domain-agnostic presentation exception.
     */
    public function item(object $item): array
    {
        if (! method_exists($item, 'toArray')) {
            throw new InvalidArgumentException(sprintf(
                'HTTP presenter item [%s] must define toArray().',
                $item::class,
            ));
        }

        $data = $item->toArray();

        if (! is_array($data)) {
            throw new InvalidArgumentException(sprintf(
                'HTTP presenter item [%s] toArray() must return array.',
                $item::class,
            ));
        }

        return $data;
    }

    /**
     * @param  Collection<int, object>  $data
     * @return array{data: list<array<string, mixed>>, meta: array<string, mixed>}
     *
     * Шаги:
     * - Сериализовать коллекцию элементов в поле data.
     * - Сериализовать metadata object тем же item() contract.
     * - Собрать стандартную envelope-структуру paginated response.
     */
    public function page(Collection $data, object $meta): array
    {
        return [
            'data' => $this->collection($data),
            'meta' => $this->item($meta),
        ];
    }
}
