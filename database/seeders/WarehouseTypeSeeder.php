<?php

namespace Database\Seeders;

use App\Warehouse\Import\Infrastructure\Models\Type;
use Illuminate\Database\Seeder;

/**
 * Сидирует справочник типов Warehouse-номенклатуры. Портирован 1:1 из dan-center TypeSeeder — все
 * 17 типов с их стабильными двухбуквенными кодами (char), на которые опирается
 * TypeTemplateResolver во всех Warehouse-фичах (Export/Import/Packaging/KitProperties).
 */
class WarehouseTypeSeeder extends Seeder
{
    /**
     * Запускает сидирование.
     */
    public function run(): void
    {
        $types = [
            ['name' => 'Колодки', 'char' => 'BP'],
            ['name' => 'Свечи зажигания', 'char' => 'SP'],
            ['name' => 'Щетки стеклоочистителя', 'char' => 'WB'],
            ['name' => 'Фильтр масляный', 'char' => 'OF'],
            ['name' => 'Фильтр воздушный', 'char' => 'AF'],
            ['name' => 'Фильтр салонный', 'char' => 'CF'],
            ['name' => 'Адаптер стеклоочистителя', 'char' => 'AW'],
            ['name' => 'Ремень ГРМ', 'char' => 'TB'],
            ['name' => 'Ремень клиновой', 'char' => 'VB'],
            ['name' => 'Подшипник ступицы', 'char' => 'HB'],
            ['name' => 'Ступица', 'char' => 'WH'],
            ['name' => 'Наконечник рулевой тяги', 'char' => 'TE'],
            ['name' => 'Рулевая тяга', 'char' => 'TR'],
            ['name' => 'Стойка стабилизатора', 'char' => 'SL'],
            ['name' => 'Шаровая опора', 'char' => 'BJ'],
            ['name' => 'ШРУС', 'char' => 'CV'],
            ['name' => 'Ремень поликлиновой', 'char' => 'SB'],
        ];

        foreach ($types as $type) {
            Type::query()->updateOrCreate(
                ['name' => $type['name']],
                $type,
            );
        }
    }
}
