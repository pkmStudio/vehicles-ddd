<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Создает таблицу применяемости комплектов.
     */
    public function up(): void
    {
        Schema::create('kit_applicabilities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kit_id')->constrained('kits')->cascadeOnDelete();
            $table->string('target_type')->comment('ApplicabilityTargetTypeEnum');
            $table->unsignedBigInteger('target_id');
            $table->string('source')->comment('ApplicabilitySourceEnum');
            $table->string('algorithm')->nullable()->comment('KitApplicabilityAlgorithmEnum');
            $table->timestamps();

            $table->unique(['kit_id', 'target_type', 'target_id']);
            $table->index(['target_type', 'target_id']);
            $table->index('source');
            $table->index(['kit_id', 'source', 'algorithm']);
        });
    }

    /**
     * Удаляет таблицу применяемости комплектов.
     */
    public function down(): void
    {
        Schema::dropIfExists('kit_applicabilities');
    }
};
