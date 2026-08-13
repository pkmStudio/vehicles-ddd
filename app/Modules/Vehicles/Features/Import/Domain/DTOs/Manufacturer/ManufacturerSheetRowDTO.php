<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\DTOs\Manufacturer;

use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;

/**
 * Строка внешнего файлового импорта производителей после нормализации mapper-ом.
 */
final readonly class ManufacturerSheetRowDTO
{
    /**
     * Фиксирует валидированную строку external manufacturer workbook.
     */
    public function __construct(
        public int $mfaId,
        public string $name,
        public ProviderEnum $provider,
    ) {}
}
