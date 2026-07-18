<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Создаёт основную таблицу Warehouse-номенклатуры.
 */
return new class extends Migration
{
    /**
     * Создаёт таблицу `nomenclatures`.
     */
    public function up(): void
    {
        Schema::create('nomenclatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_id')->constrained('types')->comment('Тип номенклатуры (BrakePad/SparkPlug/Wiper/...)');
            $table->foreignId('brand_id')->constrained('brands')->comment('Бренд номенклатуры');

            $table->string('name')->comment('Название номенклатуры');
            $table->string('country')->comment('Страна происхождения');
            $table->string('part_number')->unique()->comment('Артикул производителя');
            $table->string('color')->comment('Цвет');
            $table->unsignedInteger('weight')->comment('Вес номенклатуры, граммы');
            $table->json('material')->comment('Список материалов (MaterialEnum), см. App\Templates');
            $table->json('vehicle_type')->comment('Список типов ТС (VehicleTypeEnum), см. App\Templates');
            $table->unsignedInteger('quantity_pak')->comment('Кол-во упаковок');
            $table->unsignedInteger('quantity_in_pak')->comment('Кол-во единиц в упаковке');
            $table->jsonb('details')->comment('Форма зависит от type_id, см. App\Templates');

            $table->timestamps();
        });
    }

    /**
     * Удаляет таблицу `nomenclatures`.
     */
    public function down(): void
    {
        Schema::dropIfExists('nomenclatures');
    }
};
