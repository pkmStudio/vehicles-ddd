<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature;

/**
 * Query DTO постраничного CRM read API Warehouse-номенклатуры.
 */
final readonly class NomenclatureCrmReadQueryDTO
{
    /**
     * Получает нормализованные параметры чтения списка номенклатуры.
     *
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        public int $page = 1,
        public int $perPage = 25,
        public ?string $search = null,
        public string $sort = 'id',
        public array $filters = [],
    ) {}

    /**
     * Собирает query DTO из HTTP query-параметров.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $search = isset($data['search']) ? trim((string) $data['search']) : '';

        return new self(
            page: max(1, (int) ($data['page'] ?? 1)),
            perPage: min(max((int) ($data['per_page'] ?? 25), 1), 100),
            search: $search === '' ? null : $search,
            sort: (string) ($data['sort'] ?? 'id'),
            filters: is_array($data['filter'] ?? null) ? $data['filter'] : [],
        );
    }
}
