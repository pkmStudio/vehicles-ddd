<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Traits;

use App\Modules\Templates\Domain\Contracts\EnumHelperInterface;
use App\Modules\Templates\Domain\Enums\BooleanOptionEnum;

/**
 * Общие переводы "хранимое имя case'а enum'а" ↔ "Excel-лейбл", нужны более чем одному
 * `<X>DetailsPresenter` (SparkPlug/OilFilter/AirFilter — одиночные select-поля, Wiper —
 * multi-select). Вынесено в трейт, а не отдельный инъектируемый сервис — чистые функции без
 * состояния и без потребности в подмене.
 */
trait FormatsExportCells
{
    /**
     * Этот метод переводит хранимое имя case'а enum'а (`->name`) обратно в Excel-лейбл.
     * Шаги:
     * 1) Если имя null — возвращает null (писать в ячейку нечего).
     * 2) Иначе резолвит case по имени через `$enumClass::fromName()`.
     * 3) Возвращает `->value` найденного case'а (или null, если имя не резолвилось ни во что).
     *
     * @param  class-string<EnumHelperInterface>  $enumClass
     */
    private function nameToLabel(string $enumClass, ?string $name): ?string
    {
        return $name === null ? null : $enumClass::fromName($name)?->value;
    }

    /**
     * Этот метод делает то же самое, что `nameToLabel()`, но для одиночной Excel-ячейки, где
     * отсутствующее значение должно быть пустой строкой, а не null.
     * Шаги:
     * 1) Зовёт `nameToLabel()` для реального перевода имени в лейбл.
     * 2) Если результат null (имя пустое или не резолвилось) — подставляет пустую строку.
     *    (Воспроизводит старый `ExportDetailsBuilder::getVarValue()`, который всегда
     *    `implode(';', ...)`, а не null, для select/conditional_select-полей.)
     *
     * @param  class-string<EnumHelperInterface>  $enumClass
     */
    private function nameToLabelCell(string $enumClass, ?string $name): string
    {
        return $this->nameToLabel($enumClass, $name) ?? '';
    }

    /**
     * Этот метод делает обратную операцию к `DetailsRowCursor::pullMultiLabel()` — превращает
     * массив хранимых имён в `;`-джойн строку Excel-лейблов.
     * Шаги:
     * 1) Для каждого имени в массиве резолвит лейбл через `nameToLabel()`.
     * 2) Пропускает имена, которые не удалось перевести в лейбл (null).
     * 3) Склеивает собранные лейблы через `;` и возвращает готовую строку для ячейки.
     *
     * @param  array<int, string>  $names
     * @param  class-string<EnumHelperInterface>  $enumClass
     */
    private function namesToLabelString(array $names, string $enumClass): string
    {
        $labels = [];
        foreach ($names as $name) {
            $label = $this->nameToLabel($enumClass, $name);
            if ($label !== null) {
                $labels[] = $label;
            }
        }

        return implode(';', $labels);
    }

    /**
     * Этот метод склеивает массив чисел в `;`-джойн строку для Excel-ячейки.
     * Шаги:
     * 1) Принимает готовый массив `float`-значений.
     * 2) Склеивает их через `;` и возвращает строку (пустой массив даёт пустую строку).
     *
     * @param  array<int, float>  $values
     */
    private function floatArrayToString(array $values): string
    {
        return implode(';', $values);
    }

    /**
     * Этот метод склеивает массив целых чисел в `;`-джойн строку для Excel-ячейки. Симметрично
     * `DetailsRowCursor::pullIntArray()`.
     *
     * @param  array<int, int>  $values
     */
    private function intArrayToString(array $values): string
    {
        return implode(';', $values);
    }

    /**
     * Этот метод рендерит булево поле как Excel-лейбл ("Да"/"Нет"), обратная операция к
     * `ParsesBooleanCells::pullBoolLabel()`.
     * Шаги:
     * 1) Если значение null — возвращает пустую строку (нечего писать).
     * 2) Иначе возвращает "Да" при true, "Нет" при false.
     */
    private function boolToLabelCell(?bool $value): string
    {
        if ($value === null) {
            return '';
        }

        return $value ? BooleanOptionEnum::TRUE->value : BooleanOptionEnum::FALSE->value;
    }
}
