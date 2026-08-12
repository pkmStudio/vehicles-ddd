<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\DTOs;

/**
 * Явный контекст запуска импорта для сценариев "внешний инициатор просит личный
 * отчёт" (VehicleMultiSheetImport/EngineMultiSheetImport/EngineCrossImport/
 * EngineSparkPlugSpecificationImport) — не для консольного TecDoc-каскада, у которого нет
 * локальных пользователей вообще. userId обязателен: источник вызова (HTTP-запрос,
 * входящее Rabbit-сообщение) всегда знает, кто просит, — заменяет неявный Auth::id()
 * явной передачей внешнего идентификатора.
 *
 * operationId — не userId — основа cache-ключа отчёта об ошибках: так конкурентные прогоны
 * (в том числе повторные от одного и того же инициатора) не затирают друг друга.
 */
final readonly class ImportRunContextDTO
{
    /**
     * Фиксирует инициатора и correlation id одного import run.
     */
    public function __construct(
        public int $userId,
        public string $operationId,
    ) {}
}
