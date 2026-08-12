<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Domain\DTOs;

final readonly class VehicleKitApplicabilityRowDTO
{
    /**
     * Описывает одну строку export-а применяемости комплекта к автомобилю.
     */
    public function __construct(
        public int $kitId,
        public string $partNumbers,
        public ?string $excelTableId,
        public int $vehicleMsId,
        public string $vehicleName,
        public ?string $generation,
        public ?int $yearFrom,
        public ?int $yearTo,
        public ?string $typeCarcase,
    ) {}
}
