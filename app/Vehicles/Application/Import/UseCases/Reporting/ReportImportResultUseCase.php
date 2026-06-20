<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Import\UseCases\Reporting;

use App\Vehicles\Domain\Contracts\Exports\ImportFailureReporterInterface;
use App\Vehicles\Domain\Contracts\Notifications\FileNotificationServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Завершение импорта: если были ошибки — выгружаем отчёт и шлём пользователю файл,
 * иначе фиксируем успех. Кэш ошибок очищается в любом случае.
 */
final readonly class ReportImportResultUseCase implements \App\Vehicles\Domain\Contracts\Import\UseCases\Reporting\ReportImportResultUseCaseInterface
{
    public function __construct(
        private ImportFailureReporterInterface $reporter,
        private FileNotificationServiceInterface $notifier,
    ) {}

    public function execute(int $userId, string $cacheKey): void
    {
        try {
            $path = $this->reporter->store(Cache::get($cacheKey, []));

            if ($path !== null) {
                $this->notifier->send($userId, $path);
            } else {
                // TODO: опубликовать IMPORT_SUCCEEDED в RabbitMQ сервису с Filament (см. OutboundEventsEnum).
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
