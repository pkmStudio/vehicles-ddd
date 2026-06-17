<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manufacturers', function (Blueprint $table) {
            $table->id();
            $table->integer('mfa_id')->comment('Внешний ID марки')->unique();
            $table->string('name')->comment('Название марки');
            $table->string('provider')->default('OD')->comment('Источник: TD|OD');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manufacturers');
    }
};
