<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Создаёт справочник типов Warehouse-номенклатуры.
 */
return new class extends Migration
{
    /**
     * Создаёт таблицу `types`.
     */
    public function up(): void
    {
        Schema::create('types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Название типа номенклатуры');
            $table->string('char', 2)->comment('Короткий буквенный код типа, напр. BP/SP/WB');

            $table->timestamps();
        });
    }

    /**
     * Удаляет таблицу `types`.
     */
    public function down(): void
    {
        Schema::dropIfExists('types');
    }
};
