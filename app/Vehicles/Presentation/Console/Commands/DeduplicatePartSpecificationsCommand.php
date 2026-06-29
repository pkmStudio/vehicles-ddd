<?php

declare(strict_types=1);

namespace App\Vehicles\Presentation\Console\Commands;

use App\Vehicles\Application\PartSpecifications\Services\PartSpecificationDeduplicationService;
use App\Vehicles\Domain\Enums\Templates\DetailTemplateEnum;
use App\Vehicles\Domain\Models\Vehicle;
use Illuminate\Console\Command;

/**
 * Консольная команда удаления дублей PartSpecification.
 */
final class DeduplicatePartSpecificationsCommand extends Command
{
    protected $signature = 'vehicles:deduplicate-part-specifications
                            {--partable-type=App\\Vehicles\\Domain\\Models\\Vehicle : Полный класс partable-модели}
                            {--partable-id= : ID partable-модели}
                            {--template=wiper : Значение DetailTemplateEnum}
                            {--dry-run : Только показать план изменений без удаления}';

    protected $description = 'Удаляет дубликаты PartSpecification с одинаковыми details внутри одной partable-модели';

    /**
     * Запускает сервис дедупликации.
     *
     * Шаги:
     * 1. Нормализует CLI-фильтры.
     * 2. Делегирует поиск и удаление дублей сервису.
     * 3. Печатает summary и возвращает код с учетом ошибок.
     */
    public function handle(PartSpecificationDeduplicationService $service): int
    {
        $partableType = (string) ($this->option('partable-type') ?: Vehicle::class);
        if (! class_exists($partableType)) {
            $this->error("partable-type не найден: {$partableType}");

            return self::FAILURE;
        }

        $partableId = $this->normalizeIntOption($this->option('partable-id'));
        $template = DetailTemplateEnum::tryFrom((string) $this->option('template'));
        if ($template === null) {
            $this->error('template должен быть значением DetailTemplateEnum.');

            return self::FAILURE;
        }

        $summary = $service->deduplicate(
            dryRun: (bool) $this->option('dry-run'),
            partableType: $partableType,
            partableId: $partableId,
            template: $template,
        );

        $this->line(
            "Итого: groups_found={$summary['groups_found']} processed={$summary['processed']} ".
            "removed={$summary['removed']} dry_run_planned={$summary['dry_run_planned']} ".
            "skipped_conflicts={$summary['skipped_conflicts']} errors={$summary['errors']}",
        );

        return $summary['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Нормализует int CLI-опцию.
     *
     * Шаги:
     * 1. Пустое значение приводит к null.
     * 2. Нечисловое значение приводит к null.
     * 3. Числовое значение приводит к int.
     */
    private function normalizeIntOption(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }
}
