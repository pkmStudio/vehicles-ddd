<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Manufacturer;

final readonly class ManufacturerCrmReadQueryDTO
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        public int $page = 1,
        public int $perPage = 25,
        public ?string $search = null,
        public string $sort = 'id',
        public array $filters = [],
    ) {}
}
