<?php

declare(strict_types=1);

namespace Tests\Unit\Warehouse\KitProperties;

use App\Warehouse\KitProperties\Application\Services\WordNumberConverter;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class WordNumberConverterTest extends TestCase
{
    private const array FEMALE_WORDS = ['колодка', 'колодки', 'колодок'];

    private const array MALE_WORDS = ['фильтр масляный', 'фильтра масляных', 'фильтров масляных'];

    #[DataProvider('femaleNumberProvider')]
    public function test_converts_number_with_female_gender_declension(int $number, string $expected): void
    {
        $this->assertSame($expected, WordNumberConverter::convertNumberToWords($number, self::FEMALE_WORDS));
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function femaleNumberProvider(): array
    {
        return [
            '1' => [1, 'одна колодка'],
            '2' => [2, 'две колодки'],
            '5' => [5, 'пять колодок'],
            '11' => [11, 'одиннадцать колодок'],
            '21' => [21, 'двадцать одна колодка'],
            '100' => [100, 'сто колодок'],
        ];
    }

    public function test_converts_number_with_male_gender_declension(): void
    {
        $this->assertSame('один фильтр масляный', WordNumberConverter::convertNumberToWords(1, self::MALE_WORDS));
        $this->assertSame('два фильтра масляных', WordNumberConverter::convertNumberToWords(2, self::MALE_WORDS));
    }

    public function test_returns_plain_number_words_without_declension_words(): void
    {
        $this->assertSame('пять', WordNumberConverter::convertNumberToWords(5));
    }

    public function test_returns_empty_string_for_negative_number(): void
    {
        $this->assertSame('', WordNumberConverter::convertNumberToWords(-1, self::FEMALE_WORDS));
    }

    public function test_declension_word_picks_correct_form(): void
    {
        $this->assertSame('колодка', WordNumberConverter::declensionWord('1', self::FEMALE_WORDS, show: false));
        $this->assertSame('колодки', WordNumberConverter::declensionWord('3', self::FEMALE_WORDS, show: false));
        $this->assertSame('колодок', WordNumberConverter::declensionWord('7', self::FEMALE_WORDS, show: false));
        $this->assertSame('5 колодок', WordNumberConverter::declensionWord('5', self::FEMALE_WORDS, show: true));
    }
}
