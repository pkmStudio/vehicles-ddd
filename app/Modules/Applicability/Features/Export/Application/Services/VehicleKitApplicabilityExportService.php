<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Application\Services;

use App\Modules\Applicability\Features\Export\Domain\Contracts\Repositories\KitApplicabilityExportRepositoryInterface;
use App\Modules\Applicability\Features\Export\Domain\Contracts\Services\VehicleKitApplicabilityExportServiceInterface;
use App\Modules\Applicability\Features\Export\Domain\DTOs\VehicleKitApplicabilityRowDTO;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use Illuminate\Support\Collection;

final readonly class VehicleKitApplicabilityExportService implements VehicleKitApplicabilityExportServiceInterface
{
    public function __construct(
        private KitApplicabilityExportRepositoryInterface $repository,
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
        return collect(array_map(
            static fn (CarcaseTypeEnum $case): array => [$case->value],
            CarcaseTypeEnum::cases(),
        ));
    }

    public function getReferenceHeadings(): array
    {
        return ['Тип кузова'];
    }
}
