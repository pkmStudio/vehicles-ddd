<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Создает таблицу engines с TecDoc identifiers, характеристиками и import metadata.
     */
    public function up(): void
    {
        Schema::create('engines', function (Blueprint $table) {
            $table->id();
            $table->integer('eng_id')->unique()->comment('Внешний ID двигателя');

            $table->string('code_engine')->comment('Код двигателя');
            $table->decimal('engine_capacity', 8, 3)->comment('Объем двигателя')->nullable();
            $table->unsignedInteger('cylinder_count')->comment('Кол-во цилиндров')->nullable();
            $table->decimal('cylinder_diameter', 6, 3)->comment('Диаметр цилиндра мм')->nullable();

            $table->unsignedSmallInteger('power_kw_start')->comment('Мощность (kw) от');
            $table->unsignedSmallInteger('power_kw_upto')->nullable()->comment('Мощность (kw) до');

            $table->unsignedSmallInteger('power_ps_start')->comment('Мощность (л.с.) от');
            $table->unsignedSmallInteger('power_ps_upto')->nullable()->comment('Мощность (л.с.) до');

            $table->unsignedInteger('number_of_valves')->nullable()->comment('Количество клапанов');
            $table->string('fuel_type')->comment('Тип топлива двигателя');

            $table->string('provider')->comment('ProviderEnum');
            $table->jsonb('allow_change_fields');

            $table->jsonb('details')->comment('Детальная информация')->nullable();
            $table->unsignedBigInteger('group_id')->nullable()->index()->comment('Номер группы двигателя');
        });
    }

    /**
     * Удаляет таблицу engines при откате схемы.
     */
    public function down(): void
    {
        Schema::dropIfExists('engines');
    }
};
