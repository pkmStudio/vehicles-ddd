<?php

declare(strict_types=1);

namespace App\Vehicles\Templates\Application\Factories;

use App\Vehicles\Templates\Domain\Contracts\EnumHelperInterface;
use RuntimeException;

/**
 * Курсор чтения Excel-строки для сборки `*DetailsData` из `fromImportRow()`. Держит саму строку
 * и текущую позицию в ней как состояние инстанса — не статика: `fromImportRow()`-фабрики
 * остаются статическими (объекта, на котором можно было бы вызвать нестатичный метод, ещё нет —
 * они его как раз строят), но вся механика чтения ячеек и перевода лейбл→имя через
 * enum-справочник — обычные методы этого объекта, вызывающий явно передаёт курсор дальше по
 * цепочке вложенных `fromCursor()`.
 *
 * Курсор отдаёт результат select-полей как САМ РЕЗОЛВНУТЫЙ case enum'а (`EnumHelperInterface`),
 * не строку — типизированный промежуточный результат. Куда его положить (`->name` в поле
 * `Data`-класса — единственный вариант, раз хранимый ключ в details JSON это имя, не значение)
 * решает уже вызывающий (`Templates\Application\Factories\DetailsDataFactory`), не курсор.
 */
final class DetailsRowCursor
{
    private int $index;

    public function __construct(
        private readonly array $row,
        int $startIndex = 0,
    ) {
        $this->index = $startIndex;
    }

    /**
     * Этот метод отдаёт текущую позицию курсора в строке.
     * Шаги:
     * 1) Возвращает текущее значение внутреннего индекса — вызывающий (внешняя
     *    `fromImportRow(array $row, int &$index)`-обёртка) синхронизирует им свою ссылку
     *    `&$index` после того, как курсор прочитал все нужные ячейки.
     */
    public function position(): int
    {
        return $this->index;
    }

    /**
     * Этот метод читает одну ячейку строки по текущей позиции курсора и двигает позицию дальше.
     * Шаги:
     * 1) Берёт значение `$row[$index]` по текущей позиции, если ключа нет — считает его null.
     * 2) Двигает внутренний индекс на 1 (следующий вызов прочитает уже следующую ячейку).
     * 3) Нормализует пустую строку в null — так же, как отсутствующую ячейку (сознательное
     *    упрощение старого поведения, где пустая строка иногда passthrough'илась как есть; ни
     *    один тест на это не завязан).
     */
    public function pullCell(): mixed
    {
        $value = $this->row[$this->index] ?? null;
        $this->index++;

        return $value === '' ? null : $value;
    }

    /**
     * Этот метод читает одну ячейку строки как целое число.
     * Шаги:
     * 1) Читает ячейку через `pullCell()`.
     * 2) Если значение null — возвращает null.
     * 3) Иначе приводит значение к `int` и возвращает.
     */
    public function pullIntCell(): ?int
    {
        $value = $this->pullCell();

        return $value === null ? null : (int) $value;
    }

    /**
     * Этот метод читает одну ячейку строки как число с плавающей точкой.
     * Шаги:
     * 1) Читает ячейку через `pullCell()`.
     * 2) Если значение null — возвращает null.
     * 3) Иначе приводит значение к `float` и возвращает.
     */
    public function pullFloatCell(): ?float
    {
        $value = $this->pullCell();

        return $value === null ? null : (float) $value;
    }

    /**
     * Этот метод читает одну ячейку — одиночный select — и резолвит её в case enum'а.
     * Шаги:
     * 1) Читает сырую ячейку через `pullCell()`.
     * 2) Резолвит прочитанный Excel-лейбл в case через `resolveLabel()`.
     *
     * @param  class-string<EnumHelperInterface>  $enumClass
     */
    public function pullLabel(string $enumClass): ?EnumHelperInterface
    {
        return $this->resolveLabel($enumClass, $this->pullCell());
    }

    /**
     * Этот метод читает одну ячейку — `;`-джойн лейблов (multi-select) — как массив case'ов.
     * Шаги:
     * 1) Читает сырую ячейку через `pullCell()`.
     * 2) Если ячейка пустая — возвращает пустой массив.
     * 3) Иначе разбивает строку по `;`, обрезает пробелы у каждого куска, пропускает пустые.
     * 4) Каждый непустой лейбл резолвит в case через `resolveLabel()` и собирает в массив.
     *
     * @param  class-string<EnumHelperInterface>  $enumClass
     * @return array<int, EnumHelperInterface>
     */
    public function pullMultiLabel(string $enumClass): array
    {
        $value = $this->pullCell();
        if ($value === null) {
            return [];
        }

        $cases = [];
        foreach (explode(';', (string) $value) as $label) {
            $label = trim($label);
            if ($label === '') {
                continue;
            }
            $cases[] = $this->resolveLabel($enumClass, $label);
        }

        return $cases;
    }

    /**
     * Этот метод читает одну ячейку — `;`-джойн список чисел — как `array<float>`.
     * Шаги:
     * 1) Читает сырую ячейку через `pullCell()`.
     * 2) Если ячейка пустая — возвращает пустой массив.
     * 3) Иначе разбивает строку по `;`, обрезает пробелы у каждого куска.
     * 4) Каждый кусок приводит к `float` (воспроизводит поведение старого `DetailsBuilder` для
     *    `array`-полей — там всегда `(float)`, даже если `itemType` в DSL был `integer`;
     *    сознательно не чиним это несоответствие типа в рамках текущего рефакторинга).
     *
     * @return array<int, float>
     */
    public function pullFloatArray(): array
    {
        $value = $this->pullCell();
        if ($value === null) {
            return [];
        }

        $result = [];
        foreach (explode(';', (string) $value) as $item) {
            $result[] = (float) trim($item);
        }

        return $result;
    }

    /**
     * Этот метод резолвит Excel-лейбл в case enum'а (не в его `->name` — это решает вызывающий).
     * Шаги:
     * 1) Если лейбл null или пустая строка — возвращает null (писать нечего, не ошибка).
     * 2) Иначе ищет case по лейблу через `$enumClass::fromLabel()`.
     * 3) Если case не найден — бросает `RuntimeException` с именем справочника и значением
     *    (та же реакция на непонятное значение, что была у старого `DetailsBuilder::getVarKey()`).
     * 4) Возвращает найденный case.
     *
     * @param  class-string<EnumHelperInterface>  $enumClass
     */
    private function resolveLabel(string $enumClass, mixed $label): ?EnumHelperInterface
    {
        if ($label === null || $label === '') {
            return null;
        }

        $case = $enumClass::fromLabel((string) $label);

        if ($case === null) {
            throw new RuntimeException(sprintf(
                'Не найдено совпадение в справочнике %s. Значение: %s',
                $enumClass,
                $label,
            ));
        }

        return $case;
    }
}
