<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\DTOs;

/**
 * Явный контекст запуска импорта для сценариев "конкретный пользователь просит личный
 * отчёт" (VehicleMultiSheetImport/EngineMultiSheetImport/EngineCrossImport/
 * EngineSparkPlugSpecificationImport) — не для консольного TecDoc-каскада, у которого нет
 * концепции пользователя вообще. userId обязателен: источник вызова (HTTP-запрос, входящее
 * Rabbit-сообщение) всегда знает, кто просит, — заменяет неявный Auth::id() явной передачей.
 *
 * runId — не userId — основа cache-ключа отчёта об ошибках: так конкурентные прогоны
 * (в том числе повторные от одного и того же пользователя) не затирают друг друга.
 */
final readonly class ImportRunContext
{
    public function __construct(
        public int $userId,
        public string $runId,
    ) {}
}
