<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Presentation\Console\Commands;

use App\Modules\Applicability\Features\Calculation\Domain\Contracts\UseCases\CalculateKitApplicabilityUseCaseInterface;
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

    public function handle(CalculateKitApplicabilityUseCaseInterface $useCase): int
    {
        $kitId = $this->option('kit-id') === null ? null : (int) $this->option('kit-id');
        $chunk = max(1, (int) $this->option('chunk'));
        $runId = (string) Str::uuid();

        if ((bool) $this->option('queue')) {
            CalculateKitApplicabilityJob::dispatch(
                kitId: $kitId,
                chunk: $chunk,
                runId: $runId,
            );

            $this->info("Расчёт применяемости поставлен в очередь, runId={$runId}");

            return self::SUCCESS;
        }

        $result = $useCase->execute(
            kitId: $kitId,
            chunk: $chunk,
            runId: $runId,
        );

        $this->info(sprintf(
            'runId=%s processed=%d calculated=%d skipped=%d failed=%d',
            $result->runId,
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
