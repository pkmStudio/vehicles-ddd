<?php

declare(strict_types=1);

namespace App\Warehouse\Maintenance\Presentation\Console\Commands;

use App\Warehouse\Maintenance\Application\Services\CleanupBrakePadsPackDimensionsService;
use Illuminate\Console\Command;

/**
 * Консольная команда удаления неиспользуемых упаковочных размеров колодок.
 */
final class CleanupBrakePadsPackDimensionsCommand extends Command
{
    protected $signature = 'warehouse:cleanup-brake-pads-pack-dimensions
                            {--dry-run : Только показать кандидатов без удаления}';

    protected $description = 'Удаляет упаковочные размеры колодок, не использующиеся ни одним набором';

    /**
     * Запускает сервис очистки и печатает итог.
     */
    public function handle(CleanupBrakePadsPackDimensionsService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $summary = $service->cleanup(dryRun: $dryRun);

        if ($dryRun) {
            foreach ($summary['candidates'] as $candidate) {
                $this->line("Кандидат на удаление: #{$candidate->id} {$candidate->name}");
            }
        }

        $this->line(
            "Итого: candidates={$summary['candidates']->count()} deleted={$summary['deleted']} ".
            "skipped={$summary['skipped']}",
        );

        return self::SUCCESS;
    }
}
