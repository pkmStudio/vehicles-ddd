<?php

namespace Database\Seeders;

use App\Warehouse\Import\Infrastructure\Models\Brand;
use Illuminate\Database\Seeder;

/**
 * Сидирует справочник брендов Warehouse. Портирован 1:1 из dan-center BrandSeeder — реальные
 * номера/сроки сертификатов, не заглушки.
 */
class WarehouseBrandSeeder extends Seeder
{
    /**
     * Запускает сидирование.
     */
    public function run(): void
    {
        $brands = [
            [
                'name' => 'SUFIX',
                'char' => 'S',
                'number_sert' => 'ЕАЭС RU С-RU.НА96.В.02341/22',
                'date_start' => '2022-08-15',
                'date_begin' => '2026-08-14',
            ],
            [
                'name' => 'LYNXauto',
                'char' => 'L',
                'number_sert' => 'ЕАЭС RU С-JP.АД50.В.04977/22',
                'date_start' => '2022-08-31',
                'date_begin' => '2026-08-30',
            ],
            [
                'name' => 'DAN',
                'char' => 'D',
                'number_sert' => 'ЕАЭС KG417/026.RU.02.18502',
                'date_start' => '2025-03-21',
                'date_begin' => '2029-03-20',
            ],
        ];

        foreach ($brands as $brand) {
            Brand::query()->updateOrCreate(
                ['name' => $brand['name']],
                $brand,
            );
        }
    }
}
