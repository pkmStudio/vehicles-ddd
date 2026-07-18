<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\DTOs;

/**
 * Явный контекст запуска экспорта по внешнему RabbitMQ-запросу — симметрично
 * `Import\Domain\DTOs\ImportRunContextDTO`. `userId` — внешний инициатор (источник
 * вызова всегда его знает, замена неявного `Auth::id()`). `runId` — основа
 * cache-ключа идемпотентности и имени сгенерированного файла: конкурентные
 * прогоны (в том числе повторная Rabbit-доставка) не должны затирать друг друга.
 */
final readonly class ExportRunContextDTO
{
    public function __construct(
        public int $userId,
        public string $runId,
    ) {}
}
