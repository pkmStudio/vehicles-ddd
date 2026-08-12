<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Factories;

use App\Modules\Templates\Domain\Contracts\EnumHelperInterface;
use App\Modules\Templates\Domain\Exceptions\DetailsDataBuildException;
use App\Modules\Templates\Domain\Exceptions\InvalidDetailsCellException;
use App\Modules\Templates\Domain\Exceptions\UnknownEnumValueException;

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
     * 3) Иначе проверяет, что значение является целым числом, и только после этого приводит его к
     *    `int`.
     */
    public function pullIntCell(): ?int
    {
        $value = $this->pullCell();

        return $this->parseInt($value, 'целое число');
    }

    public function pullRequiredIntCell(string $field): int
    {
        $value = $this->pullIntCell();
        if ($value === null) {
            throw DetailsDataBuildException::requiredField($field);
        }

        return $value;
    }

    /**
     * Этот метод читает одну ячейку строки как строку (для простых текстовых полей — там, где
     * ячейка не select и не резолвится через enum-справочник).
     * Шаги:
     * 1) Читает ячейку через `pullCell()`.
     * 2) Если значение null — возвращает null.
     * 3) Иначе приводит значение к `string` и возвращает.
     */
    public function pullStringCell(): ?string
    {
        $value = $this->pullCell();

        return $value === null ? null : (string) $value;
    }

    public function pullRequiredStringCell(string $field): string
    {
        $value = $this->pullStringCell();
        if ($value === null || trim($value) === '') {
            throw DetailsDataBuildException::requiredField($field);
        }

        return $value;
    }

    /**
     * Этот метод читает одну ячейку строки как число с плавающей точкой.
     * Шаги:
     * 1) Читает ячейку через `pullCell()`.
     * 2) Если значение null — возвращает null.
     * 3) Иначе проверяет, что значение является числом, и только после этого приводит его к
     *    `float`.
     */
    public function pullFloatCell(): ?float
    {
        $value = $this->pullCell();

        return $this->parseFloat($value, 'число');
    }

    public function pullRequiredFloatCell(string $field): float
    {
        $value = $this->pullFloatCell();
        if ($value === null) {
            throw DetailsDataBuildException::requiredField($field);
        }

        return $value;
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
     * @param  class-string<EnumHelperInterface>  $enumClass
     */
    public function pullRequiredLabel(string $enumClass, string $field): EnumHelperInterface
    {
        $case = $this->pullLabel($enumClass);
        if ($case === null) {
            throw DetailsDataBuildException::requiredField($field);
        }

        return $case;
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
     * @param  class-string<EnumHelperInterface>  $enumClass
     * @return array<int, EnumHelperInterface>
     */
    public function pullRequiredMultiLabel(string $enumClass, string $field): array
    {
        $cases = $this->pullMultiLabel($enumClass);
        if ($cases === []) {
            throw DetailsDataBuildException::requiredField($field);
        }

        return $cases;
    }

    /**
     * Этот метод читает одну ячейку — `;`-джойн список чисел — как `array<float>`.
     * Шаги:
     * 1) Читает сырую ячейку через `pullCell()`.
     * 2) Если ячейка пустая — возвращает пустой массив.
     * 3) Иначе разбивает строку по `;`, обрезает пробелы у каждого куска.
     * 4) Каждый непустой кусок проверяет как число и только после этого приводит к `float`.
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
            $item = trim($item);
            if ($item === '') {
                continue;
            }

            $result[] = $this->parseFloat($item, 'числовой список');
        }

        return $result;
    }

    /**
     * @return array<int, float>
     */
    public function pullRequiredFloatArray(string $field): array
    {
        $values = $this->pullFloatArray();
        if ($values === []) {
            throw DetailsDataBuildException::requiredField($field);
        }

        return $values;
    }

    /**
     * Этот метод читает одну ячейку — `;`-джойн список чисел — как `array<int>`. В отличие от
     * `pullFloatArray()` — для полей, где источник (Nomenclature-миграция) явно объявляет
     * колонку как `integer`, не `float`.
     * Шаги:
     * 1) Читает сырую ячейку через `pullCell()`.
     * 2) Если ячейка пустая — возвращает пустой массив.
     * 3) Иначе разбивает строку по `;`, обрезает пробелы, каждый непустой кусок проверяет как
     *    целое число и только после этого приводит к `int`.
     *
     * @return array<int, int>
     */
    public function pullIntArray(): array
    {
        $value = $this->pullCell();
        if ($value === null) {
            return [];
        }

        $result = [];
        foreach (explode(';', (string) $value) as $item) {
            $item = trim($item);
            if ($item === '') {
                continue;
            }

            $result[] = $this->parseInt($item, 'числовой список');
        }

        return $result;
    }

    /**
     * @return array<int, int>
     */
    public function pullRequiredIntArray(string $field): array
    {
        $values = $this->pullIntArray();
        if ($values === []) {
            throw DetailsDataBuildException::requiredField($field);
        }

        return $values;
    }

    /**
     * Этот метод резолвит Excel-лейбл в case enum'а (не в его `->name` — это решает вызывающий).
     * Шаги:
     * 1) Если лейбл null или пустая строка — возвращает null (писать нечего, не ошибка).
     * 2) Иначе ищет case по лейблу через `$enumClass::fromLabel()`.
     * 3) Если case не найден — бросает доменную ошибку с именем справочника и значением.
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
            throw UnknownEnumValueException::label($enumClass, $label);
        }

        return $case;
    }

    private function parseInt(mixed $value, string $field): ?int
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);
        if ($normalized === '' || filter_var($normalized, FILTER_VALIDATE_INT) === false) {
            throw InvalidDetailsCellException::numeric($field, $value);
        }

        return (int) $normalized;
    }

    private function parseFloat(mixed $value, string $field): ?float
    {
        if ($value === null) {
            return null;
        }

        $normalized = str_replace(',', '.', trim((string) $value));
        if ($normalized === '' || ! is_numeric($normalized)) {
            throw InvalidDetailsCellException::numeric($field, $value);
        }

        return (float) $normalized;
    }
}
