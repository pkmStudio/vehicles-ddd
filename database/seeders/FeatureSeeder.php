<?php

namespace Database\Seeders;

use App\Vehicles\Import\Infrastructure\Models\Feature;
use App\Vehicles\Import\Infrastructure\Models\FeatureValue;
use App\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use Illuminate\Database\Seeder;

class FeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $features = [
            ['entity_type' => PartableTypeEnum::VEHICLE->value, 'name' => 'Количество дверей сзади'],
            ['entity_type' => PartableTypeEnum::VEHICLE->value, 'name' => 'Наличие колеса сзади'],
            ['entity_type' => PartableTypeEnum::VEHICLE->value, 'name' => 'Страна сборки'],
        ];

        $featureValues = [
            ['feature' => 'Количество дверей сзади', 'name' => 'Одна дверь сзади', 'short_code' => 'TD'],
            ['feature' => 'Количество дверей сзади', 'name' => 'Две двери сзади', 'short_code' => 'SD'],
            ['feature' => 'Наличие колеса сзади', 'name' => 'Есть колесо сзади', 'short_code' => 'WT'],
            ['feature' => 'Наличие колеса сзади', 'name' => 'Нет колеса сзади', 'short_code' => 'WF'],
            ['feature' => 'Страна сборки', 'name' => 'Великобритания', 'short_code' => 'GB'],
            ['feature' => 'Страна сборки', 'name' => 'Китай', 'short_code' => 'CN'],
            ['feature' => 'Страна сборки', 'name' => 'Корея', 'short_code' => 'KR'],
            ['feature' => 'Страна сборки', 'name' => 'Франция', 'short_code' => 'FR'],
            ['feature' => 'Страна сборки', 'name' => 'Япония', 'short_code' => 'JP'],
            ['feature' => 'Страна сборки', 'name' => 'США', 'short_code' => 'US'],
        ];

        foreach ($features as $feature) {
            Feature::query()->updateOrCreate(
                ['name' => $feature['name']],
                [
                    'entity_type' => $feature['entity_type'],
                    'name' => $feature['name'],
                ],
            );
        }

        foreach ($featureValues as $featureValue) {
            $feature = Feature::query()->where('name', $featureValue['feature'])->first();
            FeatureValue::query()->updateOrCreate(
                ['name' => $featureValue['name']],
                [
                    'feature_id' => $feature->id,
                    'short_code' => $featureValue['short_code'],
                ],
            );
        }
    }
}
