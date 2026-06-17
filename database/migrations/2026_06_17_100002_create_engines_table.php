<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('engines', function (Blueprint $table) {
            $table->id();
            $table->integer('eng_id')->unique()->comment('Внешний ID двигателя');
            $table->string('code_engine')->comment('Код двигателя')->nullable();
            $table->string('engine_capacity')->comment('Объем двигателя')->nullable();
            $table->integer('cylinder_count')->comment('Кол-во цилиндров')->nullable();
            $table->float('cylinder_diameter')->comment('Диаметр цилиндра мм')->nullable();
            $table->jsonb('details')->comment('Детальная информация')->nullable();
            $table->smallInteger('eng_power_kw_start')->nullable()->comment('Мощность (kw) от');
            $table->smallInteger('eng_power_kw_upto')->nullable()->comment('Мощность (kw) до');
            $table->smallInteger('eng_power_ps_start')->nullable()->comment('Мощность (л.с.) от');
            $table->smallInteger('eng_power_ps_upto')->nullable()->comment('Мощность (л.с.) до');
            $table->integer('eng_number_of_valves')->nullable()->comment('Количество клапанов');
            $table->string('eng_fuel_type')->nullable()->comment('Тип топлива двигателя');
            $table->unsignedBigInteger('group_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('engines');
    }
};
