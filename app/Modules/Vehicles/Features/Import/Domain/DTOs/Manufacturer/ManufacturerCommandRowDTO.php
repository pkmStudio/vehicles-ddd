<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\DTOs\Manufacturer;

final readonly class ManufacturerCommandRowDTO
{
    /**
     * Фиксирует валидированную строку command-импорта производителя из TecDoc cascade.
     */
    public function __construct(
        public int $mfaId,
        public string $name,
    ) {}
}
