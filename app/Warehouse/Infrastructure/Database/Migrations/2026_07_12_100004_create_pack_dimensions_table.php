<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pack_dimensions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Название упаковки');
            $table->integer('weight')->comment('Вес');
            $table->integer('width')->comment('Ширина');
            $table->integer('height')->comment('Высота');
            $table->integer('length')->comment('Длина');
            $table->integer('price')->comment('Цена');
            $table->boolean('generated')->default(false)->comment('Сгенерирована ли упаковка автоматически');

            $table->foreignId('type_id')->constrained('types')->comment('Тип номенклатуры, для которой рассчитана эта упаковка');

            $table->index('type_id');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pack_dimensions');
    }
};
