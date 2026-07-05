<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Application\Services\Reporting;

use App\Vehicles\Import\Domain\Contracts\Services\Reporting\ReportImportResultServiceInterface;
use App\Vehicles\Import\Domain\Contracts\Exports\ImportFailureReporterInterface;
use App\Vehicles\Import\Domain\Contracts\Notifications\FileNotificationServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Завершение импорта: если были ошибки — выгружаем отчёт и шлём пользователю файл,
 * иначе фиксируем успех. Кэш ошибок очищается в любом случае.
 */
final readonly class ReportImportResultService implements ReportImportResultServiceInterface
{
    public function __construct(
        private ImportFailureReporterInterface $reporter,
        private FileNotificationServiceInterface $notifier,
    ) {}

    public function report(int $userId, string $cacheKey): void
    {
        try {
            $path = $this->reporter->store(Cache::get($cacheKey, []));

            if ($path !== null) {
                $this->notifier->send($userId, $path);
            } else {
                // TODO: опубликовать IMPORT_SUCCEEDED в RabbitMQ сервису с Filament (config/rabbit-transport.php outbound).
                Log::info('Import completed without failures', ['user_id' => $userId]);
            }
        } catch (Throwable $e) {
            Log::error('Import error export failed', ['exception' => $e]);
            // TODO: опубликовать IMPORT_FAILED в RabbitMQ сервису с Filament.
        } finally {
            Cache::forget($cacheKey);
        }
    }
}
