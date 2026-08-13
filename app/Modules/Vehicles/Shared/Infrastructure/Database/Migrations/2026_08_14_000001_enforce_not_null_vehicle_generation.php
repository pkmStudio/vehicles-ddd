<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Заполняет отсутствующее поколение и восстанавливает ограничение NOT NULL.
     */
    public function up(): void
    {
        DB::statement(<<<'SQL'
            UPDATE vehicles
            SET generation = COALESCE(NULLIF(BTRIM(generation_short), ''), 'Не указано')
            WHERE generation IS NULL
            SQL);

        DB::statement('ALTER TABLE vehicles ALTER COLUMN generation SET NOT NULL');
    }

    /**
     * Возвращает только прежнюю допустимость NULL без потери заполненных значений.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE vehicles ALTER COLUMN generation DROP NOT NULL');
    }
};
