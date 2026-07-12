<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Создаёт таблицу связей Warehouse-номенклатуры с внешними системами.
 */
return new class extends Migration
{
    /**
     * Создаёт таблицу `nomenclature_integrations`.
     */
    public function up(): void
    {
        Schema::create('nomenclature_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nomenclature_id')->nullable()->constrained('nomenclatures')->comment('Номенклатура, с которой связана интеграция');
            $table->string('provider', 64)->comment('Внешняя система, напр. moysklad/1c');
            $table->uuid('external_id')->nullable()->comment('ID сущности во внешней системе');
            $table->string('external_code', 255)->nullable()->comment('Код сущности во внешней системе');
            $table->string('sync_status', 32)->default('pending')->comment('Статус последней синхронизации');
            $table->timestamp('synced_at')->nullable()->comment('Когда синхронизация последний раз прошла успешно');
            $table->text('last_error')->nullable()->comment('Текст последней ошибки синхронизации');
            $table->string('payload_hash', 64)->nullable()->comment('Хэш последнего отправленного/полученного payload — для дедупликации');

            $table->unique(['provider', 'external_id']);
            $table->unique(['provider', 'nomenclature_id']);

            $table->timestamps();
        });
    }

    /**
     * Удаляет таблицу `nomenclature_integrations`.
     */
    public function down(): void
    {
        Schema::dropIfExists('nomenclature_integrations');
    }
};
