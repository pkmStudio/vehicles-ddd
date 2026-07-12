<?php

declare(strict_types=1);

namespace App\Warehouse\KitProperties\Domain\Contracts\Services;

/**
 * Порт генерации человекочитаемого текста комплектации набора ("В комплекте 2 щётки...").
 */
interface KitComplectationServiceInterface
{
    /**
     * Формирует текст комплектации по количеству, названию типа и списку ключей материала.
     *
     * @param  array<int, string>  $material  ключи материала (например `NICKEL`), не лейблы
     */
    public function describe(int $quantity, string $typeName, array $material): string;
}
