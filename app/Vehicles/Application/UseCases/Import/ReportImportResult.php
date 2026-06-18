<?php

declare(strict_types=1);

namespace App\Vehicles\Application\UseCases\Import;

use App\User\Models\User;
use App\Vehicles\Application\Contracts\Exports\ImportFailureReporterInterface;
use App\Vehicles\Application\Contracts\Notifications\FileNotificationServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Завершение импорта: если были ошибки — выгружаем отчёт и шлём пользователю файл,
 * иначе фиксируем успех. Кэш ошибок очищается в любом случае.
 */
final readonly class ReportImportResult
{
    public function __construct(
        private ImportFailureReporterInterface $reporter,
        private FileNotificationServiceInterface $notifier,
    ) {}

    public function execute(User $user, string $cacheKey): void
    {
        try {
            $path = $this->reporter->store(Cache::get($cacheKey, []));

            if ($path !== null) {
                $this->notifier->send($user, $path);
            } else {
                // TODO: опубликовать IMPORT_SUCCEEDED в RabbitMQ сервису с Filament (см. OutboundEventsEnum).
                Log::info('Import completed without failures', ['user_id' => $user->id]);
            }
        } catch (Throwable $e) {
            Log::error('Import error export failed', ['exception' => $e]);
            // TODO: опубликовать IMPORT_FAILED в RabbitMQ сервису с Filament.
        } finally {
            Cache::forget($cacheKey);
        }
    }
}
