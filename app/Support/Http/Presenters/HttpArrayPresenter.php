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
     */
    public function page(Collection $data, object $meta): array
    {
        return [
            'data' => $this->collection($data),
            'meta' => $this->item($meta),
        ];
    }
}
