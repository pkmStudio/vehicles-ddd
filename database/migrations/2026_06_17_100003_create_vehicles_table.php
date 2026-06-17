<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->references('id')->on('vehicles');
            $table->foreignId('manufacturer_id')->constrained('manufacturers');
            $table->integer('mfa_id')->comment('Внешний ID марки');
            $table->integer('ms_id')->unique()->comment('Внешний ID модели');
            $table->string('name');
            $table->string('generation')->nullable();
            $table->string('generation_short')->nullable();
            $table->string('type')->comment('VehicleTypeEnum');
            $table->string('type_carcase')->comment('CarcaseTypeEnum');
            $table->string('provider')->default('OD')->comment('Источник: TD|OD');
            $table->integer('generation_year_from')->nullable()->comment('Год от');
            $table->integer('generation_year_to')->nullable()->comment('Год до');
            $table->boolean('is_allow')->default(false)->comment('Разрешено');
            $table->string('localized_name')->nullable();
            $table->string('steering_type')->default('Левый руль')->comment('SteeringTypeEnum');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
