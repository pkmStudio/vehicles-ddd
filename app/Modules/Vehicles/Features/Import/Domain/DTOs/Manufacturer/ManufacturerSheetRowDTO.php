<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\DTOs\Manufacturer;

/**
 * Строка внешнего файлового импорта производителей: mfa_id, name, provider — все три колонки
 * обязательны (в отличие от ManufacturerCommandRowDTO, консольный TecDoc-каскад, где provider
 * всегда TD). Пустая/отсутствующая колонка — ошибка строки, а не повод для дефолта; это
 * проверяет ManufacturerSheetRowMapper до создания DTO.
 */
final readonly class ManufacturerSheetRowDTO
{
    public function __construct(
        public int $mfaId,
        public string $name,
        public string $provider,
    ) {}
}
