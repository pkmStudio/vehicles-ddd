<?php

namespace Database\Seeders;

use App\Modules\Warehouse\Features\Import\Infrastructure\Models\PackDimension;
use App\Modules\Warehouse\Features\Import\Infrastructure\Models\Type;
use Illuminate\Database\Seeder;

/**
 * Сидирует стартовые упаковочные размеры Warehouse. Портирован 1:1 из dan-center
 * PakDimensionSeeder (реальные габариты/вес/цена — не заглушки). Критичен для SparkPlugs/Wiper/
 * OilFilter/WiperAdapter: их Packaging-стратегии никогда не создают коробку автоматически (см.
 * `*PackagingStrategy` — только 4 из 8 типов не имеют auto-create fallback), поэтому без сидов на
 * пустой БД расчёт свойств набора для этих типов падает. AirFilter/CabinFilter тоже сидируются
 * (хотя их стратегии умеют создавать коробку сами) — чтобы использовать проверенные бизнесом
 * размеры вместо авто-сгенерированных с первого попавшегося товара.
 *
 * dan-center хранил `type_id` как хардкод int (`TypeEnum::X->value`) — в dan-vehicles `types.id`
 * назначается автоинкрементом, поэтому здесь резолвим по стабильному `char` через
 * WarehouseTypeSeeder (должен быть выполнен раньше).
 */
class WarehousePackDimensionSeeder extends Seeder
{
    /**
     * Запускает сидирование.
     */
    public function run(): void
    {
        $typeIdsByChar = Type::query()
            ->whereIn('char', ['SP', 'WB', 'OF', 'AW', 'AF', 'CF'])
            ->pluck('id', 'char');

        $dimensions = [
            // Свечи
            ['name' => 'Коробка для свечей (S)', 'weight' => 29, 'width' => 100, 'height' => 100, 'length' => 50, 'price' => 15, 'char' => 'SP'],
            ['name' => 'Коробка для свечей (L)', 'weight' => 28, 'width' => 150, 'height' => 100, 'length' => 100, 'price' => 15, 'char' => 'SP'],

            // Щётки
            ['name' => 'Коробка для передних щеток (M)', 'weight' => 97, 'width' => 65, 'height' => 58, 'length' => 745, 'price' => 35, 'char' => 'WB'],
            ['name' => 'Коробка для передних щеток (L)', 'weight' => 107, 'width' => 65, 'height' => 58, 'length' => 830, 'price' => 50, 'char' => 'WB'],
            ['name' => 'Коробка для задних щеток (S)', 'weight' => 43, 'width' => 60, 'height' => 31, 'length' => 526, 'price' => 25, 'char' => 'WB'],

            // Масляные фильтры
            ['name' => 'Коробка для масляного фильтра (S)', 'weight' => 28, 'width' => 90, 'height' => 90, 'length' => 120, 'price' => 15, 'char' => 'OF'],
            ['name' => 'Коробка для масляного фильтра (L)', 'weight' => 38, 'width' => 150, 'height' => 100, 'length' => 90, 'price' => 24, 'char' => 'OF'],

            // Адаптеры
            ['name' => 'Пакет для адаптеров', 'weight' => 5, 'width' => 110, 'height' => 70, 'length' => 50, 'price' => 1, 'char' => 'AW'],

            // Воздушные фильтры
            ['name' => 'Коробка для воздушных фильтров 1', 'weight' => 85, 'width' => 260, 'height' => 200, 'length' => 70, 'price' => 40, 'char' => 'AF'],
            ['name' => 'Коробка для воздушных фильтров 2', 'weight' => 79, 'width' => 295, 'height' => 180, 'length' => 50, 'price' => 38, 'char' => 'AF'],
            ['name' => 'Коробка для воздушных фильтров 3', 'weight' => 146, 'width' => 320, 'height' => 260, 'length' => 60, 'price' => 50, 'char' => 'AF'],
            ['name' => 'Коробка для воздушных фильтров 4', 'weight' => 125, 'width' => 320, 'height' => 200, 'length' => 90, 'price' => 45, 'char' => 'AF'],
            ['name' => 'Коробка для воздушных фильтров 5', 'weight' => 116, 'width' => 240, 'height' => 175, 'length' => 175, 'price' => 45, 'char' => 'AF'],
            ['name' => 'Коробка для воздушных фильтров 6', 'weight' => 109, 'width' => 235, 'height' => 235, 'length' => 70, 'price' => 45, 'char' => 'AF'],
            ['name' => 'Коробка для воздушных фильтров 7', 'weight' => 110, 'width' => 420, 'height' => 190, 'length' => 55, 'price' => 47, 'char' => 'AF'],
            ['name' => 'Коробка для воздушных фильтров 8', 'weight' => 136, 'width' => 265, 'height' => 260, 'length' => 60, 'price' => 47, 'char' => 'AF'],
            ['name' => 'Коробка для воздушных фильтров 9', 'weight' => 91, 'width' => 365, 'height' => 150, 'length' => 85, 'price' => 40, 'char' => 'AF'],
            ['name' => 'Коробка для воздушных фильтров 10', 'weight' => 145, 'width' => 370, 'height' => 240, 'length' => 70, 'price' => 50, 'char' => 'AF'],
            ['name' => 'Коробка для воздушных фильтров 11', 'weight' => 103, 'width' => 340, 'height' => 180, 'length' => 90, 'price' => 43, 'char' => 'AF'],

            // Салонные фильтры
            ['name' => 'Коробка салонного фильтра', 'weight' => 136, 'width' => 265, 'height' => 260, 'length' => 60, 'price' => 47, 'char' => 'CF'],
        ];

        foreach ($dimensions as $dimension) {
            $char = $dimension['char'];
            unset($dimension['char']);

            $typeId = $typeIdsByChar[$char] ?? null;
            if ($typeId === null) {
                continue;
            }

            $dimension['type_id'] = $typeId;

            PackDimension::query()->updateOrCreate(
                ['name' => $dimension['name']],
                $dimension,
            );
        }
    }
}
