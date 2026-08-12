<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Создает pivot-таблицу engine_modification между engines и modifications.
     */
    public function up(): void
    {
        Schema::create('engine_modification', function (Blueprint $table) {
            $table->id();

            $table->foreignId('engine_id')->constrained('engines');
            $table->foreignId('modification_id')->constrained('modifications');

            $table->integer('eng_id')->comment('Внешний ID двигателя');
            $table->integer('mod_id')->comment('Внешний ID модификации');
            $table->string('type')->comment('VehicleTypeEnum');

            $table->unique(['engine_id', 'modification_id']);
            $table->unique(['eng_id', 'mod_id', 'type']);
        });
    }

    /**
     * Удаляет pivot-таблицу engine_modification при откате схемы.
     */
    public function down(): void
    {
        Schema::dropIfExists('engine_modification');
    }
};
