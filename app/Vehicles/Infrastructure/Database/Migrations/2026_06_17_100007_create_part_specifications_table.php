<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('part_specifications', function (Blueprint $table) {
            $table->id();
            $table->morphs('partable'); // PartableTypeEnum::{VEHICLE,ENGINE} — App\Vehicles\Shared\Domain\Enums
            $table->foreignId('feature_value_id')->nullable()->constrained('feature_values');
            $table->string('template')->comment('DetailTemplateEnum');
            $table->string('name')->nullable()->comment('Приписка к названию');
            $table->text('text')->nullable()->comment('Приписка к описанию');
            $table->jsonb('details')->comment('Заполненный шаблон');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('part_specifications');
    }
};
