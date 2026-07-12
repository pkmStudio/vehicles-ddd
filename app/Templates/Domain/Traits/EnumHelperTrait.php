<?php

declare(strict_types=1);

namespace App\Templates\Domain\Traits;

/**
 * Для enum-словарей полей details (`Shared\Templates\Domain\Enums\*`), где хранимый ключ —
 * `case->name`, а `case->value` — русский лейбл для Excel. Переводит между ними в обе стороны.
 */
trait EnumHelperTrait
{
    /**
     * Этот метод находит case enum'а по его Excel-лейблу (`->value`).
     * Шаги:
     * 1) Если лейбл null или пустая строка после trim — сразу возвращает null (нечего искать).
     * 2) Иначе перебирает все case'ы enum'а.
     * 3) Сравнивает `->value` каждого case'а с лейблом регистронезависимо, с обрезкой пробелов.
     * 4) Возвращает первый совпавший case, либо null, если совпадений не нашлось.
     */
    public static function fromLabel(?string $label): ?static
    {
        if ($label === null || trim($label) === '') {
            return null;
        }

        return array_find(self::cases(),
            fn($case) => mb_strtolower(trim($case->value)) === mb_strtolower(trim($label)));
    }

    /**
     * Этот метод находит case enum'а по хранимому имени (`->name` — то, что реально лежит в
     * details JSON).
     * Шаги:
     * 1) Если имя null — сразу возвращает null (нечего искать).
     * 2) Иначе перебирает все case'ы enum'а.
     * 3) Сравнивает `->name` каждого case'а с переданным именем точно (без учёта регистра/пробелов).
     * 4) Возвращает первый совпавший case, либо null, если совпадений не нашлось.
     */
    public static function fromName(?string $name): ?static
    {
        if ($name === null) {
            return null;
        }

        return array_find(self::cases(), fn($case) => $case->name === $name);
    }
}
