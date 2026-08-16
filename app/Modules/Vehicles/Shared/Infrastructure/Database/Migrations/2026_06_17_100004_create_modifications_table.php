<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Создает таблицу modifications с TecDoc identifiers и техническими характеристиками.
     */
    public function up(): void
    {
        Schema::create('modifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles');

            $table->integer('ms_id')->comment('Внешний ID модели');
            $table->integer('mod_id')->comment('Внешний ID модификации');

            $table->year('year_from')->comment('Начало периода выпуска модификации');
            $table->year('year_to')->nullable();

            $table->string('localized_name')->nullable()->comment('Локализованное название');
            $table->string('description')->comment('Описание модификации из Текдока');
            $table->string('description_short')->nullable()->comment('Наше описание модификации');
            $table->string('type')->comment('VehicleTypeEnum');

            $table->smallInteger('power_ps')->comment('Мощность (л.с.)');
            $table->smallInteger('power_kw')->comment('Мощность (kw)');

            $table->string('brake_system_type')->nullable()->comment('BrakeSystemTypeEnum');
            $table->string('engine_type')->comment('EngineTypeEnum');
            $table->string('gear_type')->nullable()->comment('GearTypeEnum');
            $table->string('drive_type')->nullable()->comment('DriveTypeEnum');

            $table->smallInteger('number_of_cylinders')->nullable()->comment('Количество цилиндров');
            $table->float('capacity_lt')->nullable()->comment('Объем двигателя (л.)');

            $table->string('provider')->comment('ProviderEnum');
            $table->jsonb('allow_change_fields');

            $table->unique(['mod_id', 'type']);
        });
    }

    /**
     * Удаляет таблицу modifications при откате схемы.
     */
    public function down(): void
    {
        Schema::dropIfExists('modifications');
    }
};
