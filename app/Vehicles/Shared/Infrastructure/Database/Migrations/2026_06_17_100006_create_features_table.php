<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('features', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type')->comment('Указатель на сущность особенности');
            $table->string('name')->comment('Название особенности');
            $table->timestamps();
        });

        Schema::create('feature_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feature_id')->comment('Указатель на особенность');
            $table->string('name')->comment('Вариация особенности');
            $table->string('short_code')->comment('Короткое обозначение особенности');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_values');
        Schema::dropIfExists('features');
    }
};
