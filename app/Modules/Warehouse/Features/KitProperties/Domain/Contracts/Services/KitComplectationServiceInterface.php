<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\KitProperties\Domain\Contracts\Services;

/**
 * Порт генерации человекочитаемого текста комплектации набора ("В комплекте 2 щётки...").
 */
interface KitComplectationServiceInterface
{
    /**
     * Формирует текст комплектации по количеству, названию типа и списку ключей материала.
     * Шаги:
     * 1) Принять количество, название типа и ключи материала primary-позиции.
     * 2) Нормализовать количество и материал в человекочитаемые части фразы.
     * 3) Вернуть строку комплектации для сохранения в Kit.
     *
     * @param  array<int, string>  $material  ключи материала (например `NICKEL`), не лейблы
     */
    public function describe(int $quantity, string $typeName, array $material): string;
}
