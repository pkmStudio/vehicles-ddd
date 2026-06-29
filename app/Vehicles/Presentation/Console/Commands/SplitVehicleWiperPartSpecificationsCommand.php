<?php

declare(strict_types=1);

namespace App\Vehicles\Presentation\Console\Commands;

use App\Vehicles\Application\PartSpecifications\Services\VehicleWiperPartSpecificationSplitService;
use Illuminate\Console\Command;

/**
 * Консольная команда разделения legacy PartSpecification дворников по сторонам.
 */
final class SplitVehicleWiperPartSpecificationsCommand extends Command
{
    protected $signature = 'vehicles:split-wiper-part-specifications
                            {--dry-run : Показывает план изменений без записи в БД}
                            {--chunk=200 : Размер обработки за один проход}';

    protected $description = 'Разделяет смешанные/мульти-адаптерные wiper PartSpecification на отдельные записи';

    /**
     * Запускает сервис разделения спецификаций.
     *
     * Шаги:
     * 1. Читает CLI-опции dry-run и chunk.
     * 2. Делегирует работу сервису application-слоя.
     * 3. Печатает summary и возвращает успешный код.
     */
    public function handle(VehicleWiperPartSpecificationSplitService $service): int
    {
        $summary = $service->split(
            dryRun: (bool) $this->option('dry-run'),
            chunk: max(1, (int) $this->option('chunk')),
        );

        $this->line(
            "Итого: found={$summary['found']} processed={$summary['processed']} ".
            "created={$summary['created']} removed={$summary['removed']} skipped={$summary['skipped']}",
        );

        return self::SUCCESS;
    }
}
