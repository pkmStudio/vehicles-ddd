<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Application\Services;

use App\Modules\Applicability\Features\Export\Domain\Contracts\Repositories\KitApplicabilityExportRepositoryInterface;
use App\Modules\Applicability\Features\Export\Domain\Contracts\Services\VehicleKitApplicabilityExportServiceInterface;
use App\Modules\Applicability\Features\Export\Domain\Contracts\Services\VehicleKitApplicabilityReferenceServiceInterface;
use App\Modules\Applicability\Features\Export\Domain\DTOs\VehicleKitApplicabilityRowDTO;
use Illuminate\Support\Collection;

final readonly class VehicleKitApplicabilityExportService implements VehicleKitApplicabilityExportServiceInterface
{
    public function __construct(
        private KitApplicabilityExportRepositoryInterface $repository,
        private VehicleKitApplicabilityReferenceServiceInterface $references,
    ) {}

    public function getRows(): Collection
    {
        return $this->repository->vehicleRows();
    }

    public function mapRow(mixed $row): array
    {
        /** @var VehicleKitApplicabilityRowDTO $row */
        return [
            $row->kitId,
            $row->partNumbers,
            $row->excelTableId,
            $row->vehicleMsId,
            $row->vehicleName,
            $row->generation,
            $row->yearFrom,
            $row->yearTo,
            $row->typeCarcase,
        ];
    }

    public function getHeadings(): array
    {
        return [
            'ID комплекта',
            'Состав комплекта',
            'ID гугл таблицы',
            'ID модели',
            'Модель',
            'Поколение',
            'Год от',
            'Год до',
            'Тип кузова',
        ];
    }

    public function getReferenceRows(): Collection
    {
        return $this->references->carcaseTypeRows();
    }

    public function getReferenceHeadings(): array
    {
        return ['Тип кузова'];
    }
}
