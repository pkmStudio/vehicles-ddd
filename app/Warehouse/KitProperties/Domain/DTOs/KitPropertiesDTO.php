<?php

declare(strict_types=1);

namespace App\Warehouse\KitProperties\Domain\DTOs;

/**
 * Итог расчёта производных свойств набора. Плоский readonly DTO (не `spatie/laravel-data`) — как
 * и в dan-center `KitPropertiesDto`, это чисто внутрипроцессный результат, не снимок модели.
 */
final readonly class KitPropertiesDTO
{
    /**
     * Хранит рассчитанные свойства набора, готовые к записи в `kits` вызывающей фичей.
     */
    public function __construct(
        public int $typeId,
        public ?int $packDimensionId,
        public float $weight,
        public int $quantityInPackage,
        public int $quantityPackage,
        public string $complectation,
        public string $importHash,
    ) {}
}
