<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Название бренда');
            $table->string('number_sert')->comment('Номер сертификата');
            // Названия полей унаследованы из dan-center как есть — там нет ни докблока, ни
            // явного смысла у пары date_start/date_begin (оба звучат как "начало"), уточнить
            // семантику у бизнеса перед реальным использованием этих полей.
            $table->dateTime('date_start')->comment('Смысл поля не задокументирован в dan-center, уточнить у бизнеса');
            $table->dateTime('date_begin')->comment('Смысл поля не задокументирован в dan-center, уточнить у бизнеса');
            $table->string('char', 1)->nullable()->comment('Короткий буквенный код бренда');

            $table->index('name');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
