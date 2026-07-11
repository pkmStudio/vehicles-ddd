<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles');

            $table->integer('ms_id')->comment('Внешний ID модели');
            $table->integer('mod_id')->comment('Внешний ID модификации');

            $table->year('year_from')->nullable();
            $table->year('year_to')->nullable();
            $table->string('description')->nullable();
            $table->string('type')->comment('VehicleTypeEnum');

            $table->string('brake_system_type')->nullable()->comment('BrakeSystemTypeEnum');
            $table->smallInteger('power_ps')->nullable()->comment('Мощность (л.с.)');
            $table->smallInteger('power_kw')->nullable()->comment('Мощность (kw)');
            $table->string('engine_type')->nullable()->comment('EngineTypeEnum');
            $table->string('gear_type')->nullable()->comment('GearTypeEnum');
            $table->string('drive_type')->nullable()->comment('DriveTypeEnum');
            $table->string('localized_name')->nullable()->comment('Локализованное название');
            $table->smallInteger('number_of_cylinders')->nullable()->comment('Количество цилиндров');
            $table->float('capacity_lt')->nullable()->comment('Объем двигателя (л.)');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modifications');
    }
};
