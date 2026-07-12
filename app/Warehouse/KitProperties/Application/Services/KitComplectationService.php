<?php

declare(strict_types=1);

namespace App\Warehouse\KitProperties\Application\Services;

use App\Warehouse\KitProperties\Domain\Contracts\Services\KitComplectationServiceInterface;

/**
 * Генерирует текст комплектации набора ("В комплекте {число словами} {склонение типа}[.
 * Материал: ...]"). Порт `KitComplectationService`+`SiteEnumList::KIT_TYPE_COMPLECTATION` из
 * dan-center. `MATERIAL_LABELS` — своя копия таблицы (в dan-vehicles нет backed `MaterialEnum`,
 * только приватные лейбл-таблицы у Export/Import/здесь для той же цели, значения должны совпадать
 * 1:1 с `Warehouse\Export\Application\Services\Rows\NomenclatureExportRow::MATERIAL_LABELS`).
 */
final readonly class KitComplectationService implements KitComplectationServiceInterface
{
    /**
     * Русское название типа (`types.name`) → словоформы [1, 2-4, 5+] для склонения числительного.
     * Порт `SiteEnumList::KIT_TYPE_COMPLECTATION` из dan-center — 17 записей, 1:1 с
     * `NomenclatureDetailTemplateEnum`.
     */
    private const array KIT_TYPE_COMPLECTATION = [
        'Колодки' => ['колодка', 'колодки', 'колодок'],
        'Свечи зажигания' => ['свеча зажигания', 'свечи зажигания', 'свечей зажигания'],
        'Щетки стеклоочистителя' => ['щетка стеклоочистителя', 'щетки стеклоочистителя', 'щеток стеклоочистителя'],
        'Фильтр масляный' => ['фильтр масляный', 'фильтра масляных', 'фильтров масляных'],
        'Фильтр воздушный' => ['фильтр воздушный', 'фильтра воздушных', 'фильтров воздушных'],
        'Фильтр салонный' => ['фильтр салонный', 'фильтра салонных', 'фильтров салонных'],
        'Адаптер стеклоочистителя' => [
            'адаптер стеклоочистителя', 'адаптера стеклоочистителя', 'адаптеров стеклоочистителя',
        ],
        'Ремень ГРМ' => ['ремень ГРМ', 'ремня ГРМ', 'ремней ГРМ'],
        'Ремень клиновой' => ['ремень клиновой', 'ремня клиновых', 'ремней клиновых'],
        'Ремень поликлиновой' => ['ремень поликлиновой', 'ремня поликлиновых', 'ремней поликлиновых'],
        'Подшипник ступицы' => ['подшипник ступицы', 'подшипника ступицы', 'подшипников ступицы'],
        'Ступица' => ['ступица', 'ступицы', 'ступиц'],
        'Наконечник рулевой тяги' => [
            'наконечник рулевой тяги', 'наконечника рулевой тяги', 'наконечников рулевой тяги',
        ],
        'Рулевая тяга' => ['рулевая тяга', 'рулевые тяги', 'рулевых тяг'],
        'Стойка стабилизатора' => ['стойка стабилизатора', 'стойки стабилизатора', 'стоек стабилизатора'],
        'Шаровая опора' => ['шаровая опора', 'шаровые опоры', 'шаровых опор'],
        'ШРУС' => ['ШРУС', 'ШРУСа', 'ШРУСов'],
    ];

    /**
     * Ключ материала (хранимый в `nomenclatures.material`) → русский лейбл. Обратная сторона
     * `NomenclatureExportRow::MATERIAL_LABELS`.
     */
    private const array MATERIAL_LABELS = [
        'NICKEL' => 'Никель',
        'PLATINUM' => 'Платина',
        'IRIDIUM' => 'Иридий',
        'DOUBLE_IRIDIUM' => 'Двойной иридий',
        'DOUBLE_PLATINUM' => 'Двойная платина',
        'DOUBLE_NICKEL' => 'Двойной никель',
        'STEEL' => 'Сталь',
        'METAL' => 'Металл',
        'ABS_PLASTIC' => 'ABS пластик',
        'PAPER' => 'Бумага',
        'RUBBER' => 'Резина',
        'PLASTIC' => 'Пластик',
    ];

    /**
     * Этот метод формирует текст комплектации набора.
     *
     * Шаги:
     * 1) Найти словоформы для типа в справочнике; если типа нет в справочнике — вернуть пустую
     *    строку (то же поведение, что у dan-center — не бросать исключение на неизвестном типе).
     * 2) Записать число словами с нужным склонением.
     * 3) Если передан материал — дописать русские лейблы через запятую.
     *
     * @param  array<int, string>  $material
     */
    public function describe(int $quantity, string $typeName, array $material): string
    {
        if (! isset(self::KIT_TYPE_COMPLECTATION[$typeName])) {
            return '';
        }

        $base = 'В комплекте ';
        $base .= WordNumberConverter::convertNumberToWords($quantity, self::KIT_TYPE_COMPLECTATION[$typeName]);

        if ($material === []) {
            return $base;
        }

        $labels = array_map(
            fn (string $key): string => self::MATERIAL_LABELS[mb_strtoupper($key)] ?? $key,
            $material,
        );

        return $base.'. Материал: '.implode(', ', $labels);
    }
}
