<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Создаёт справочник брендов Warehouse-номенклатуры.
 */
return new class extends Migration
{
    /**
     * Создаёт таблицу `brands`.
     */
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Название бренда');
            $table->string('number_sert')->comment('Номер сертификата');
            $table->dateTime('date_start')->comment('Дата начала действия сертификата');
            $table->dateTime('date_end')->comment('Дата окончания действия сертификата');
            $table->string('char', 1)->nullable()->comment('Короткий буквенный код бренда');

            $table->timestamps();
        });
    }

    /**
     * Удаляет таблицу `brands`.
     */
    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
