<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Создает таблицу vehicles с иерархией моделей и ссылкой на производителя.
     */
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->references('id')->on('vehicles');
            $table->foreignId('manufacturer_id')->constrained('manufacturers');

            $table->integer('mfa_id')->comment('Внешний ID марки');
            $table->integer('ms_id')->unique()->comment('Внешний ID модели');

            $table->string('name');
            $table->string('localized_name')->nullable();
            $table->string('excel_table_id')->nullable()->comment('ID таблицы Excel');

            $table->string('generation')->comment('Описание поколения из TecDoc');
            $table->string('generation_short')->nullable()->comment('Наше описание поколения');
            $table->year('generation_year_from')->comment('Год поколения от');
            $table->year('generation_year_to')->nullable()->comment('Год поколения до');

            $table->string('type')->comment('VehicleTypeEnum');
            $table->string('type_carcase')->comment('CarcaseTypeEnum');
            $table->string('provider')->default('OD')->comment('ProviderEnum');

            $table->string('steering_type')->default('Левый руль')->comment('SteeringTypeEnum');
            $table->boolean('is_allow')->default(false)->comment('Разрешено для работы');
        });
    }

    /**
     * Удаляет таблицу vehicles при откате схемы.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
