<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kit_nomenclature', function (Blueprint $table) {
            $table->id();

            $table->foreignId('nomenclature_id')->constrained('nomenclatures')->comment('Номенклатура, входящая в набор');
            $table->foreignId('kit_id')->constrained('kits')->comment('Набор, в который входит номенклатура');
            $table->unsignedSmallInteger('sort')->default(0)->comment('Порядок номенклатур внутри набора');

            $table->index(['kit_id', 'nomenclature_id']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kit_nomenclature');
    }
};
