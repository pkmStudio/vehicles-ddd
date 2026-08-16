<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\DTOs\Modification;

/**
 * Нормализованная строка manager-файла модификаций.
 */
final readonly class ModificationSheetRowDTO
{
    /**
     * Фиксирует значения листа `Модификации`.
     */
    public function __construct(
        public int $msId,
        public ?int $modId,
        public ?string $localizedName,
        public int $yearFrom,
        public ?int $yearTo,
        public ?float $capacityLt,
        public string $engineType,
        public int $powerPs,
        public int $powerKw,
        public ?string $driveType,
        public ?string $gearType,
        public ?string $brakeSystemType,
        public ?int $numberOfCylinders,
        public string $description,
        public ?string $descriptionShort,
        public ?string $type,
    ) {}
}
