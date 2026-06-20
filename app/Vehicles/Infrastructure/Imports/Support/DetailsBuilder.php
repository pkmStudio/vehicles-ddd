<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Imports\Support;

/**
 * Сборка details из строки по шаблону.
 *
 * Отвечает за формирование структуры details при импорте данных из Excel.
 * Рекурсивно обходит шаблон полей и извлекает значения из строки импорта.
 * Обрабатывает различные типы данных и преобразует их в формат для сохранения в БД.
 */
final readonly class DetailsBuilder
{
    /**
     * Строит массив details на основе строки импорта и шаблона.
     * Рекурсивно обходит все поля шаблона и извлекает соответствующие значения.
     *
     * @param  array  $row  Массив данных строки импорта
     * @param  int  &$startIndex  Текущая позиция в строке (передается по ссылке)
     * @param  array  $template  Конфигурация шаблона полей
     * @return array Сформированный массив details для сохранения
     *
     * @throws \Exception При ошибках преобразования данных
     *
     * @example
     * $row = ['Дворник', 'H-Click', '500мм'];
     * $startIndex = 0;
     * $template = [
     *     'name' => ['type' => 'string'],
     *     'mount' => ['type' => 'select', 'variables' => ['h_click' => 'H-Click']],
     *     'length' => ['type' => 'string']
     * ];
     * // Результат: ['name' => 'Дворник', 'mount' => 'h_click', 'length' => '500мм']
     */
    public function buildDetails(array $row, int &$startIndex, array $template): array
    {
        $details = [];
        foreach ($template as $fieldKey => $fieldConfig) {
            $value = $this->getFieldValue($row, $startIndex, $fieldConfig);
            $details[$fieldKey] = $value;
        }

        return $details;
    }

    /**
     * Рекурсивно извлекает значение поля из строки импорта.
     * Обрабатывает различные типы полей:
     * - Простые типы (string, integer, float)
     * - Select поля (с преобразованием значений в ключи)
     * - Conditional select (с объединением опций из нескольких источников)
     * - Array поля (с разбором строки с разделителем)
     * - Вложенные поля (children)
     *
     * @param  array  $row  Массив данных строки импорта
     * @param  int  &$currentIndex  Текущая позиция в строке (передается по ссылке)
     * @param  array  $fieldConfig  Конфигурация поля
     * @return mixed Извлеченное значение (может быть строкой, массивом, числом или null)
     *
     * @throws \Exception Если значение не найдено в справочнике variables
     *
     * @example
     * // Для поля типа select с multiple = true
     * $row = ['H-Click;Side Pin', ...];
     * $fieldConfig = [
     *     'type' => 'select',
     *     'multiple' => true,
     *     'variables' => ['h_click' => 'H-Click', 'side_pin' => 'Side Pin']
     * ];
     * // Результат: ['h_click', 'side_pin']
     */
    private function getFieldValue(array $row, int &$currentIndex, array $fieldConfig): mixed
    {
        $rowValue = null;

        if (isset($fieldConfig['children']) && is_array($fieldConfig['children'])) {
            $nestedData = [];
            $hasNestedData = false;

            foreach ($fieldConfig['children'] as $childKey => $childConfig) {
                $value = $this->getFieldValue($row, $currentIndex, $childConfig);
                $nestedData[$childKey] = $value;
                $hasNestedData = true;
            }

            return $hasNestedData ? $nestedData : null;
        }

        if (isset($row[$currentIndex])) {
            $rowValue = $row[$currentIndex];
        }

        $currentIndex++;

        if ($fieldConfig['type'] === 'select' && isset($fieldConfig['variables'])) {
            // Для multiple select (массив значений)
            if (isset($fieldConfig['multiple']) && $fieldConfig['multiple'] === true) {
                $value = $this->getVarKeys((string) $rowValue, $fieldConfig['variables']);
            } else {
                $value = $this->getVarKey((string) $rowValue, $fieldConfig['variables']);
            }
        } elseif ($fieldConfig['type'] === 'conditional_select' && is_array($fieldConfig['options_source'] ?? null)) {
            // Объединяем все источники опций
            $variables = [];
            foreach ($fieldConfig['options_source'] as $array) {
                $variables = array_replace($variables, $array);
            }

            // Для multiple select (массив значений)
            if (isset($fieldConfig['multiple']) && $fieldConfig['multiple'] === true) {
                $value = $this->getVarKeys((string) $rowValue, $variables);
            } else {
                $value = $this->getVarKey((string) $rowValue, $variables);
            }
        } elseif ($fieldConfig['type'] === 'array') {
            $values = explode(';', (string) $rowValue);
            $result = [];

            foreach ($values as $val) {
                $result[] = (float) trim($val);
            }
            $value = $result;
        } else {
            // Простые типы данных
            $value = ctype_digit($rowValue) ? (int) $rowValue : $rowValue;
        }

        return $value;
    }

    /**
     * Преобразует строку значений в массив ключей согласно справочнику variables.
     * Используется для multiple select полей при импорте.
     *
     * @param  string|null  $value  Строка со значениями, разделенными точкой с запятой
     * @param  array  $variables  Справочник соответствия ключ => значение
     * @return array Массив найденных ключей
     *
     * @example
     * $value = "H-Click;Side Pin";
     * $variables = ['h_click' => 'H-Click', 'side_pin' => 'Side Pin', 'bayonet' => 'Bayonet'];
     * // Результат: ['h_click', 'side_pin']
     */
    private function getVarKeys(?string $value, array $variables): array
    {
        if (empty($value)) {
            return [];
        }

        $values = explode(';', $value);
        $result = [];

        foreach ($values as $val) {
            $val = trim($val);
            foreach ($variables as $varKey => $varValue) {
                if (mb_strtolower(trim((string) $varValue)) === mb_strtolower($val)) {
                    $result[] = $varKey;
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Преобразует строковое значение в ключ согласно справочнику variables.
     * Используется для одиночных select полей при импорте.
     *
     * @param  string  $value  Строковое значение для поиска
     * @param  array  $variables  Справочник соответствия ключ => значение
     * @return string|null Найденный ключ или null если значение пустое
     *
     * @throws \Exception Если значение не найдено в справочнике
     *
     * @example
     * $value = "H-Click";
     * $variables = ['h_click' => 'H-Click', 'side_pin' => 'Side Pin'];
     * // Результат: 'h_click'
     */
    private function getVarKey(string $value, array $variables): ?string
    {
        $result = null;
        $found = false;
        foreach ($variables as $varKey => $varValue) {
            if (mb_strtolower(trim((string) $varValue)) === mb_strtolower(trim($value))) {
                $result = $varKey;
                $found = true;
                break;
            }
        }

        if (! $found && ! empty($value)) {
            $varString = implode(', ', $variables);
            throw new \Exception("Не найдено совпадение в системе. Значение: $value, в $varString");
        }

        return $result;
    }
}
