<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Shared\Infrastructure\Logging;

use Psr\Log\AbstractLogger;
use Stringable;

/**
 * Сериализуемый PSR logger proxy для queued imports/jobs.
 */
final class LaravelLoggerProxy extends AbstractLogger
{
    /**
     * Делегирует запись актуальному Laravel logger из контейнера.
     *
     * @param  array<string, mixed>  $context
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        logger()->log($level, (string) $message, $context);
    }
}
