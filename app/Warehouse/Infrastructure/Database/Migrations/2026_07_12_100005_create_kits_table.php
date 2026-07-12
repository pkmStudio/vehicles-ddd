<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Создаёт таблицу Warehouse-наборов.
 */
return new class extends Migration
{
    /**
     * Создаёт таблицу `kits`.
     */
    public function up(): void
    {
        Schema::create('kits', function (Blueprint $table) {
            $table->id();
            $table->string('complectation')->comment('Комплектация');
            $table->integer('guarantee')->comment('Гарантия, мес.');
            $table->integer('quantity_in_package')->comment('Кол-во в упаковке');
            $table->integer('quantity_package')->comment('Кол-во заводских упаковок');
            $table->boolean('complement')->comment('Комплект');
            $table->integer('weight')->comment('Вес набора');
            $table->string('import_hash')->nullable()->comment('Хэш состава набора — для дедупликации при импорте');
            $table->boolean('is_sale_separately')->default(false)->comment('Продаётся ли отдельно от набора');
            $table->boolean('is_active')->default(true)->comment('Активен ли набор');

            $table->foreignId('pack_dimension_id')->constrained('pack_dimensions')->comment('Подобранная упаковка набора');
            $table->foreignId('type_id')->constrained('types')->comment('Тип номенклатур, из которых состоит набор');

            $table->timestamps();
        });
    }

    /**
     * Удаляет таблицу `kits`.
     */
    public function down(): void
    {
        Schema::dropIfExists('kits');
    }
};
