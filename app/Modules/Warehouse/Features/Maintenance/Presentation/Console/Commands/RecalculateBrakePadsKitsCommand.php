<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Maintenance\Presentation\Console\Commands;

use App\Modules\Warehouse\Features\Maintenance\Application\Services\RecalculateBrakePadsKitsService;
use Illuminate\Console\Command;

/**
 * Консольная команда пересчёта свойств уже существующих наборов колодок.
 */
final class RecalculateBrakePadsKitsCommand extends Command
{
    protected $signature = 'warehouse:recalculate-brake-pads-kits
                            {--dry-run : Только показать, что изменилось бы, без записи}
                            {--chunk=200 : Размер чанка выборки наборов}';

    protected $description = 'Пересчитывает вес/упаковку/комплектацию наборов колодок через KitProperties';

    /**
     * Запускает сервис пересчёта и печатает итог.
     */
    public function handle(RecalculateBrakePadsKitsService $service): int
    {
        $summary = $service->recalculate(
            dryRun: (bool) $this->option('dry-run'),
            chunk: (int) $this->option('chunk'),
        );

        $this->line(
            "Итого: updated={$summary['updated']} unchanged={$summary['unchanged']} ".
            "failed={$summary['failed']}",
        );

        return $summary['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
