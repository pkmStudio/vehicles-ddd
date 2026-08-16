<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Presentation\Console\Commands;

use App\Modules\Applicability\Features\Calculation\Application\UseCases\CalculateKitApplicabilityUseCase;
use App\Modules\Applicability\Features\Calculation\Infrastructure\Jobs\CalculateKitApplicabilityJob;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

final class CalculateKitApplicabilityCommand extends Command
{
    protected $signature = 'applicability:calculate-kits
        {--kit-id= : ID набора для точечного пересчёта}
        {--chunk=1000 : Размер чанка чтения наборов}
        {--queue : Поставить расчёт в очередь}';

    protected $description = 'Рассчитать применяемость активных наборов без автослушателей событий';

    /**
     * Запускает ручной пересчет применяемости комплектов из Artisan.
     *
     * Шаги:
     * 1. Читает optional kit id и нормализует chunk size из command options.
     * 2. Создает operation id для текущего запуска.
     * 3. Если указан `--queue`, dispatch-ит job и выводит operation id.
     * 4. Иначе синхронно вызывает use case пересчета.
     * 5. Печатает агрегированные счетчики и ошибки отдельных kit-ов.
     * 6. Возвращает failure exit code только если есть failed kits.
     */
    public function handle(CalculateKitApplicabilityUseCase $useCase): int
    {
        $kitId = $this->option('kit-id') === null ? null : (int) $this->option('kit-id');
        $chunk = max(1, (int) $this->option('chunk'));
        $operationId = (string) Str::uuid();

        if ((bool) $this->option('queue')) {
            CalculateKitApplicabilityJob::dispatch(
                kitId: $kitId,
                chunk: $chunk,
                operationId: $operationId,
            );

            $this->info("Расчёт применяемости поставлен в очередь, operationId={$operationId}");

            return self::SUCCESS;
        }

        $result = $useCase->execute(
            kitId: $kitId,
            chunk: $chunk,
            operationId: $operationId,
        );

        $this->info(sprintf(
            'operationId=%s processed=%d calculated=%d skipped=%d failed=%d',
            $result->operationId,
            $result->processedKits,
            $result->calculatedKits,
            $result->skippedKits,
            $result->failedKits,
        ));

        foreach ($result->errors as $error) {
            $this->warn($error);
        }

        return $result->failedKits === 0 ? self::SUCCESS : self::FAILURE;
    }
}
