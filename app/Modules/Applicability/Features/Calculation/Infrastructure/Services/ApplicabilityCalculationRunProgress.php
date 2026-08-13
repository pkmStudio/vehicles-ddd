<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Infrastructure\Services;

use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Calculation\KitApplicabilityCalculationResultDTO;
use App\Modules\Applicability\Features\Calculation\Infrastructure\Jobs\FinalizeKitApplicabilityCalculationJob;
use Illuminate\Support\Facades\Cache;

final readonly class ApplicabilityCalculationRunProgress
{
    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FINALIZING = 'finalizing';

    public const STATUS_COMPLETED_WITH_FAILURES = 'completed_with_failures';

    /**
     * Создает временное состояние запуска расчета.
     *
     * Шаги:
     * 1. Собирает aggregate-state с counters, chunks и metadata запуска.
     * 2. Пишет state через atomic Cache::add, чтобы повторный request не перетер текущий расчет.
     * 3. Ограничивает хранение TTL, чтобы незавершенный запуск не завис в Redis навсегда.
     *
     * @param  array<int, array<int, int>>  $chunks
     */
    public function startRun(
        string $operationId,
        int $userId,
        ?int $kitId,
        int $chunkSize,
        array $chunks,
    ): bool {
        return Cache::add(
            key: $this->runKey($operationId),
            value: [
                'operation_id' => $operationId,
                'user_id' => $userId,
                'kit_id' => $kitId,
                'chunk_size' => $chunkSize,
                'status' => self::STATUS_RUNNING,
                'chunks_total' => count($chunks),
                'chunks_completed' => 0,
                'chunks_failed' => 0,
                'processed_kits' => 0,
                'calculated_kits' => 0,
                'skipped_kits' => 0,
                'failed_kits' => 0,
                'affected_kit_ids' => [],
                'errors' => [],
                'chunks' => $chunks,
                'started_at' => now()->toISOString(),
            ],
            ttl: now()->addSeconds($this->ttlSeconds()),
        );
    }

    /**
     * Возвращает список kit-id чанков для запуска.
     *
     * Шаги:
     * 1. Читает runtime-state по operation id.
     * 2. Возвращает пустой список, если state отсутствует или поврежден.
     * 3. Нормализует все ids к integer.
     *
     * @return array<int, array<int, int>>
     */
    public function chunks(string $operationId): array
    {
        $run = Cache::get($this->runKey($operationId), []);
        if (! is_array($run)) {
            return [];
        }

        $chunks = $run['chunks'] ?? [];
        if (! is_array($chunks)) {
            return [];
        }

        return array_map(
            static fn (array $chunk): array => array_map(static fn (int|string $id): int => (int) $id, $chunk),
            $chunks,
        );
    }

    /**
     * Учитывает успешный chunk result и при завершении всех чанков ставит finalizer job.
     *
     * Шаги:
     * 1. Передает counters и ошибки chunk-а в общий finishChunk().
     * 2. Если finishChunk() вернул operation id, значит это был последний chunk.
     * 3. Dispatch-ит finalizer только для первого потока, который закрыл расчет.
     */
    public function completeChunk(string $operationId, int $chunkIndex, KitApplicabilityCalculationResultDTO $result): void
    {
        $operationIdToFinalize = $this->finishChunk(
            operationId: $operationId,
            chunkIndex: $chunkIndex,
            status: self::STATUS_COMPLETED,
            processedKits: $result->processedKits,
            calculatedKits: $result->calculatedKits,
            skippedKits: $result->skippedKits,
            failedKits: $result->failedKits,
            affectedKitIds: $result->affectedKitIds,
            errors: $result->errors,
        );

        if ($operationIdToFinalize !== null) {
            FinalizeKitApplicabilityCalculationJob::dispatch($operationIdToFinalize);
        }
    }

    /**
     * Учитывает падение chunk job и при необходимости ставит финализацию.
     *
     * Шаги:
     * 1. Преобразует exception message в ошибку расчетного результата.
     * 2. Помечает chunk как failed через общий finishChunk().
     * 3. Dispatch-ит finalizer, если после этого завершились все чанки.
     */
    public function failChunk(string $operationId, int $chunkIndex, string $error): void
    {
        $operationIdToFinalize = $this->finishChunk(
            operationId: $operationId,
            chunkIndex: $chunkIndex,
            status: self::STATUS_COMPLETED_WITH_FAILURES,
            processedKits: 0,
            calculatedKits: 0,
            skippedKits: 0,
            failedKits: 0,
            affectedKitIds: [],
            errors: [$error],
        );

        if ($operationIdToFinalize !== null) {
            FinalizeKitApplicabilityCalculationJob::dispatch($operationIdToFinalize);
        }
    }

    /**
     * Атомарно резервирует право финализировать расчет.
     *
     * Шаги:
     * 1. Пишет marker finalization по operation id через Cache::add.
     * 2. Возвращает true только для первого caller-а.
     * 3. Держит marker по тому же TTL, что и runtime-state расчета.
     */
    public function requestFinalization(string $operationId): bool
    {
        return Cache::add(
            key: $this->finalizationKey($operationId),
            value: true,
            ttl: now()->addSeconds($this->ttlSeconds()),
        );
    }

    /**
     * Собирает aggregate result DTO из runtime cache-state.
     *
     * Шаги:
     * 1. Читает state по operation id.
     * 2. Возвращает null, если state уже очищен или отсутствует.
     * 3. Нормализует counters, affected kit ids и errors в DTO результата.
     */
    public function result(string $operationId): ?KitApplicabilityCalculationResultDTO
    {
        $run = Cache::get($this->runKey($operationId));
        if (! is_array($run)) {
            return null;
        }

        return new KitApplicabilityCalculationResultDTO(
            operationId: (string) ($run['operation_id'] ?? $operationId),
            processedKits: (int) ($run['processed_kits'] ?? 0),
            calculatedKits: (int) ($run['calculated_kits'] ?? 0),
            skippedKits: (int) ($run['skipped_kits'] ?? 0),
            failedKits: (int) ($run['failed_kits'] ?? 0) + (int) ($run['chunks_failed'] ?? 0),
            affectedKitIds: array_values(array_unique(array_map(
                static fn (int|string $id): int => (int) $id,
                is_array($run['affected_kit_ids'] ?? null) ? $run['affected_kit_ids'] : [],
            ))),
            errors: array_values(array_map(
                static fn (mixed $error): string => (string) $error,
                is_array($run['errors'] ?? null) ? $run['errors'] : [],
            )),
        );
    }

    /**
     * Удаляет все runtime cache-ключи расчета.
     *
     * Шаги:
     * 1. До удаления run-key читает список чанков.
     * 2. Удаляет aggregate-state, finalization marker и lock key.
     * 3. Удаляет idempotency markers завершенных чанков.
     */
    public function forget(string $operationId): void
    {
        $chunks = $this->chunks($operationId);

        Cache::forget($this->runKey($operationId));
        Cache::forget($this->finalizationKey($operationId));
        Cache::forget($this->lockKey($operationId));

        foreach ($chunks as $index => $_) {
            Cache::forget($this->chunkFinishedKey($operationId, (int) $index));
        }
    }

    /**
     * Финализирует состояние одного чанка под cache lock.
     *
     * Шаги:
     * 1. Захватывает lock конкретного operation id.
     * 2. Через Cache::add защищает chunk от повторного учета.
     * 3. Добавляет counters, affected ids и errors к aggregate-state.
     * 4. Если закрыт последний chunk, резервирует финализацию и возвращает operation id.
     *
     * @param  array<int, int>  $affectedKitIds
     * @param  array<int, string>  $errors
     */
    private function finishChunk(
        string $operationId,
        int $chunkIndex,
        string $status,
        int $processedKits,
        int $calculatedKits,
        int $skippedKits,
        int $failedKits,
        array $affectedKitIds,
        array $errors,
    ): ?string {
        return Cache::lock(
            name: $this->lockKey($operationId),
            seconds: 10,
        )->block(10, function () use (
            $operationId,
            $chunkIndex,
            $status,
            $processedKits,
            $calculatedKits,
            $skippedKits,
            $failedKits,
            $affectedKitIds,
            $errors,
        ): ?string {
            $run = Cache::get($this->runKey($operationId));
            if (! is_array($run)) {
                return null;
            }

            if (! Cache::add(
                key: $this->chunkFinishedKey($operationId, $chunkIndex),
                value: true,
                ttl: now()->addSeconds($this->ttlSeconds()),
            )) {
                return null;
            }

            $runAffectedKitIds = array_values(array_unique(array_merge(
                is_array($run['affected_kit_ids'] ?? null) ? $run['affected_kit_ids'] : [],
                $affectedKitIds,
            )));
            $runErrors = array_values(array_merge(
                is_array($run['errors'] ?? null) ? $run['errors'] : [],
                $errors,
            ));

            $run['processed_kits'] = (int) ($run['processed_kits'] ?? 0) + $processedKits;
            $run['calculated_kits'] = (int) ($run['calculated_kits'] ?? 0) + $calculatedKits;
            $run['skipped_kits'] = (int) ($run['skipped_kits'] ?? 0) + $skippedKits;
            $run['failed_kits'] = (int) ($run['failed_kits'] ?? 0) + $failedKits;
            $run['affected_kit_ids'] = $runAffectedKitIds;
            $run['errors'] = $runErrors;

            if ($status === self::STATUS_COMPLETED_WITH_FAILURES) {
                $run['chunks_failed'] = (int) ($run['chunks_failed'] ?? 0) + 1;
            } else {
                $run['chunks_completed'] = (int) ($run['chunks_completed'] ?? 0) + 1;
            }

            $finishedChunks = (int) ($run['chunks_completed'] ?? 0) + (int) ($run['chunks_failed'] ?? 0);
            $chunksTotal = (int) ($run['chunks_total'] ?? 0);
            if ($finishedChunks >= $chunksTotal && $chunksTotal > 0) {
                $run['status'] = self::STATUS_FINALIZING;
            }

            Cache::put(
                key: $this->runKey($operationId),
                value: $run,
                ttl: now()->addSeconds($this->ttlSeconds()),
            );

            if ($finishedChunks >= $chunksTotal && $chunksTotal > 0 && $this->requestFinalization($operationId)) {
                return $operationId;
            }

            return null;
        });
    }

    /**
     * Возвращает cache key aggregate-state запуска.
     */
    private function runKey(string $operationId): string
    {
        return sprintf((string) config('applicability.calculation.runtime.cache.keys.run'), $operationId);
    }

    /**
     * Возвращает cache key marker-а завершенного чанка.
     */
    private function chunkFinishedKey(string $operationId, int $chunkIndex): string
    {
        return sprintf((string) config('applicability.calculation.runtime.cache.keys.chunk_finished'), $operationId, $chunkIndex);
    }

    /**
     * Возвращает cache key marker-а запущенной финализации.
     */
    private function finalizationKey(string $operationId): string
    {
        return sprintf((string) config('applicability.calculation.runtime.cache.keys.finalization'), $operationId);
    }

    /**
     * Возвращает cache lock key для атомарного обновления aggregate-state.
     */
    private function lockKey(string $operationId): string
    {
        return sprintf((string) config('applicability.calculation.runtime.cache.keys.lock'), $operationId);
    }

    /**
     * Возвращает TTL runtime-state расчета в секундах.
     */
    private function ttlSeconds(): int
    {
        return max(60, (int) config('applicability.calculation.runtime.cache.ttl_seconds', 86400));
    }
}
