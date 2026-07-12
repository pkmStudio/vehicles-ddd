<?php

declare(strict_types=1);

namespace App\Warehouse\KitProperties\Application\Services;

/**
 * Преобразует число в русские числительные словами с учётом рода и склонения существительного
 * после числительного. Портирован дословно из dan-center `WordNumberConverterService` (122
 * строки, самодостаточная утилита без внешних зависимостей) — только неймспейс.
 */
final class WordNumberConverter
{
    /**
     * Этот метод преобразует число в слова, опционально дописывая согласованное существительное.
     *
     * Шаги:
     * 1) Выбрать род числительного по переданным вариантам склонения.
     * 2) Разложить число на сотни, десятки и единицы с отдельной веткой для 10-19.
     * 3) Добавить согласованное существительное, если переданы варианты склонения.
     *
     * @param  array<int, string>|null  $words  варианты склонения, например ['товар', 'товара', 'товаров']
     */
    public static function convertNumberToWords(int $number, ?array $words = null): string
    {
        $numberWords = [
            'единицы' => ['', 'один', 'два', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять'],
            'единицы_жен' => ['', 'одна', 'две', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять'],
            'десятки' => [
                '', 'десять', 'двадцать', 'тридцать', 'сорок', 'пятьдесят', 'шестьдесят', 'семьдесят', 'восемьдесят',
                'девяносто',
            ],
            'сотни' => [
                '', 'сто', 'двести', 'триста', 'четыреста', 'пятьсот', 'шестьсот', 'семьсот', 'восемьсот', 'девятьсот',
            ],
            'особые' => [
                11 => 'одиннадцать',
                12 => 'двенадцать',
                13 => 'тринадцать',
                14 => 'четырнадцать',
                15 => 'пятнадцать',
                16 => 'шестнадцать',
                17 => 'семнадцать',
                18 => 'восемнадцать',
                19 => 'девятнадцать',
            ],
        ];

        if ($number < 0) {
            return '';
        }

        $gender = 'male';
        if ($words && count($words) >= 3) {
            $firstWord = $words[0];
            if (preg_match('/[ая]$/u', $firstWord)) {
                $gender = 'female';
            }
        }

        $result = '';
        $hundreds = floor($number / 100);
        $tensUnits = $number % 100;
        $tens = floor($tensUnits / 10);
        $units = $tensUnits % 10;

        if ($hundreds > 0) {
            $result .= $numberWords['сотни'][$hundreds].' ';
        }

        if ($tensUnits >= 11 && $tensUnits <= 19) {
            $result .= $numberWords['особые'][$tensUnits];
        } else {
            if ($tens > 0) {
                $result .= $numberWords['десятки'][$tens].' ';
            }

            if ($units > 0) {
                if ($gender === 'female') {
                    $result .= $numberWords['единицы_жен'][$units];
                } else {
                    $result .= $numberWords['единицы'][$units];
                }
            }

            if ($tensUnits === 10) {
                $result .= 'десять';
            }
        }

        $result = trim($result);

        if ($words && count($words) >= 3) {
            $result .= ' '.self::declensionWord((string) $number, $words, false);
        }

        return trim($result);
    }

    /**
     * Этот метод склоняет существительное после числительного.
     *
     * @param  array<int, string>  $words  варианты склонения, например ['товар', 'товара', 'товаров']
     */
    public static function declensionWord(string $value, array $words, bool $show = true): string
    {
        $num = $value % 100;
        if ($num > 19) {
            $num %= 10;
        }

        $out = $show ? $value.' ' : '';
        $out .= match ($num) {
            1 => $words[0],
            2, 3, 4 => $words[1],
            default => $words[2],
        };

        return $out;
    }
}
