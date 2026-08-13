<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit;

final readonly class KitCrmReadQueryDTO
{
    /**
     * @param  array<string, mixed>  $фильтры
     */
    public function __construct(
        public int $page = 1,
        public int $perPage = 25,
        public ?string $search = null,
        public string $sort = 'id',
        public array $filters = [],
    ) {}
}
