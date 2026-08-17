<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Application\Services;

use App\Modules\Vehicles\Features\Export\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Services\ManufacturerExportServiceInterface;
use App\Modules\Vehicles\Features\Export\Domain\ModelData\ManufacturerData;
use Illuminate\Support\Collection;

/**
 * Собирает строки и mapping Excel-экспорта производителей автомобилей.
 */
final readonly class ManufacturerExportService implements ManufacturerExportServiceInterface
{
    /**
     * Получает read repository производителей.
     */
    public function __construct(
        private ManufacturerRepositoryInterface $manufacturers,
    ) {}

    /**
     * Возвращает typed строки производителей для Excel adapter-а.
     *
     * @return Collection<int, ManufacturerData>
     */
    public function getRows(): Collection
    {
        return $this->manufacturers->all();
    }

    /**
     * Возвращает headings листа производителей.
     *
     * @return array<int, string>
     */
    public function getHeadings(): array
    {
        return ['mfa_id', 'name', 'provider'];
    }

    /**
     * Преобразует typed manufacturer snapshot в Excel row.
     *
     * @return array{0: int, 1: string, 2: string}
     */
    public function mapRow(ManufacturerData $row): array
    {
        return [$row->mfaId, $row->name, $row->provider->value];
    }
}
