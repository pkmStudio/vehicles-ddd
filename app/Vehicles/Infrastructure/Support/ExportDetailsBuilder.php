<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Сборка экспортных данных/заголовков по шаблону.
 *
 * Отвечает за формирование данных для экспорта в Excel.
 * Рекурсивно обходит шаблон полей и извлекает соответствующие значения из спецификации.
 * Поддерживает вложенные поля, различные типы данных (select, conditional_select, array).
 */
final class ExportDetailsBuilder
{
    /**
     * Извлекает заголовки колонок из конфигурации шаблона.
     * Рекурсивно обходит все уровни вложенности и собирает названия полей.
     * Используется для формирования заголовков в Excel файле.
     *
     * @param  array  $template  Конфигурация шаблона полей
     * @return array Массив заголовков в порядке следования полей
     *
     * @example
     * $template = [
     *     'length' => ['name' => 'Длина', 'type' => 'string'],
     *     'mount' => ['name' => 'Крепление', 'type' => 'select'],
     *     'features' => [
     *         'children' => [
     *             'material' => ['name' => 'Материал', 'type' => 'string'],
     *             'color' => ['name' => 'Цвет', 'type' => 'string']
     *         ]
     *     ]
     * ];
     * // Результат: ['Длина', 'Крепление', 'Материал', 'Цвет']
     */
    public function extractHeadingsFromTemplate(array $template): array
    {
        $headings = [];

        foreach ($template as $fieldConfig) {
            if (isset($fieldConfig['children'])) {
                $childHeadings = $this->extractHeadingsFromTemplate($fieldConfig['children']);
                $headings = array_merge($headings, $childHeadings);
            } else {
                $headings[] = $fieldConfig['name'] ?? 'Unknown';
            }
        }

        return $headings;
    }

    /**
     * Формирует массив данных для строки экспорта на основе спецификации и шаблона.
     *
     * @param  Model  $model  Модель, содержащая данные (PartSpecification, Nomenclature и др.)
     * @param  array  $template  Конфигурация шаблона полей
     * @return array Массив, где значения - данные
     *
     * @throws \Exception При ошибках обработки данных (пробрасывается из getFieldValue)
     */
    public function getDetailsData(Model $model, array $template): array
    {
        $rowCells = [];
        foreach ($template as $fieldKey => $fieldConfig) {
            $this->getFieldValue($model, $rowCells, $fieldKey, $fieldConfig);
        }

        return $rowCells;
    }

    /**
     * Рекурсивно извлекает значение конкретного поля из спецификации.
     * Обрабатывает различные типы полей:
     * - Простые типы (string, integer, float)
     * - Select поля (с преобразованием ключей в значения)
     * - Conditional select (с объединением опций из нескольких источников)
     * - Array поля (с преобразованием в строку с разделителем)
     * - Вложенные поля (children)
     *
     * @param  Model  $model  Модель, содержащая данные (PartSpecification, Nomenclature и др.)
     * @param  array  &$rowCells  Массив для накопления результатов (передается по ссылке)
     * @param  string  $fieldKey  Ключ поля в шаблоне
     * @param  array  $fieldConfig  Конфигурация поля
     *
     * @example
     * // Для поля типа select
     * $fieldConfig = [
     *     'type' => 'select',
     *     'variables' => ['h_click' => 'H-Click', 'side_pin' => 'Side Pin'],
     *     'multiple' => true
     * ];
     */
    private function getFieldValue(Model $model, array &$rowCells, string $fieldKey, array $fieldConfig): void
    {
        if (isset($fieldConfig['children'])) {
            foreach ($fieldConfig['children'] as $childKey => $childConfig) {
                $this->getFieldValue($model, $rowCells, $fieldKey.'.'.$childKey, $childConfig);
            }

            return;
        }

        if ($fieldConfig['type'] === 'select' && isset($fieldConfig['variables'])) {
            // Для multiple select (массив значений)
            if (isset($fieldConfig['multiple']) && $fieldConfig['multiple'] === true) {
                $varKeys = data_get($model->details, $fieldKey, []);
                $value = $this->getVarValue($varKeys, $fieldConfig['variables']);
                $rowCells[] = $value;
            } else {
                $varKey = data_get($model->details, $fieldKey);
                $value = $this->getVarValue([$varKey], $fieldConfig['variables']);
                $rowCells[] = $value;
            }
        } elseif ($fieldConfig['type'] === 'conditional_select' && isset($fieldConfig['options_source'])) {
            // Объединяем все источники опций
            $variables = [];
            foreach ($fieldConfig['options_source'] as $array) {
                $variables = array_replace($variables, $array);
            }

            // Для multiple select (массив значений)
            if (isset($fieldConfig['multiple']) && $fieldConfig['multiple'] === true) {
                $varKeys = data_get($model->details, $fieldKey, []);
                $value = $this->getVarValue($varKeys, $variables);
                $rowCells[] = $value;
            } else {
                $varKey = data_get($model->details, $fieldKey);
                $value = $this->getVarValue([$varKey], $variables);
                $rowCells[] = $value;
            }
        } elseif ($fieldConfig['type'] === 'array') {
            // Преобразуем массив в строку с разделителем ;
            $value = implode(';', data_get($model->details, $fieldKey, []));
            $rowCells[] = $value;
        } else {
            // Простые типы данных
            $rowCells[] = data_get($model->details, $fieldKey);
        }
    }

    /**
     * Преобразует массив ключей в строку значений согласно справочнику variables.
     * Используется для multiple select полей.
     *
     * @param  array  $varKeys  Массив ключей для преобразования
     * @param  array  $variables  Справочник соответствия ключ => значение
     * @return string Строка значений, разделенных точкой с запятой
     *
     * @example
     * $varKeys = ['h_click', 'side_pin'];
     * $variables = ['h_click' => 'H-Click', 'side_pin' => 'Side Pin'];
     * // Результат: "H-Click;Side Pin"
     */
    private function getVarValue(array $varKeys, array $variables): string
    {
        $result = [];

        foreach ($varKeys as $key) {
            if (isset($variables[$key])) {
                $result[] = $variables[$key];
            }
        }

        return implode(';', $result);
    }
}
