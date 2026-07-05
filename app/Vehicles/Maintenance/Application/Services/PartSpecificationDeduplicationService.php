<?php

declare(strict_types=1);

namespace App\Vehicles\Maintenance\Application\Services;

use App\Vehicles\Templates\Domain\Enums\DetailTemplateEnum;
use App\Vehicles\Domain\Models\PartSpecification;
use App\Vehicles\Domain\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Сервис удаления дублей PartSpecification с одинаковыми details.
 */
final readonly class PartSpecificationDeduplicationService
{
    /**
     * Удаляет дубликаты PartSpecification.
     *
     * Шаги:
     * 1. Находит группы с одинаковыми partable/template/details.
     * 2. Выбирает keeper, предпочитая запись с feature_value_id.
     * 3. Удаляет остальные записи группы, если нет конфликта keeper.
     *
     * @return array<string, int>
     */
    public function deduplicate(
        bool $dryRun = false,
        ?string $partableType = Vehicle::class,
        ?int $partableId = null,
        ?DetailTemplateEnum $template = DetailTemplateEnum::WIPER,
    ): array {
        $summary = [
            'groups_found' => 0,
            'processed' => 0,
            'removed' => 0,
            'skipped_conflicts' => 0,
            'dry_run_planned' => 0,
            'errors' => 0,
        ];

        $groups = $this->duplicateGroups($partableType, $partableId, $template)->get();
        $summary['groups_found'] = $groups->count();

        foreach ($groups as $group) {
            $summary['processed']++;

            try {
                $this->processGroup($group, $dryRun, $summary);
            } catch (Throwable $e) {
                $summary['errors']++;
                Log::error('Deduplicate part specifications failed', [
                    'partable_type' => $group->partable_type ?? null,
                    'partable_id' => $group->partable_id ?? null,
                    'template' => $group->template ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $summary;
    }

    /**
     * Строит query групп дублей.
     *
     * Шаги:
     * 1. Группирует по partable/template/details.
     * 2. Ограничивает выборку переданными фильтрами.
     * 3. Возвращает только группы с count > 1.
     */
    private function duplicateGroups(?string $partableType, ?int $partableId, ?DetailTemplateEnum $template)
    {
        $query = PartSpecification::query()
            ->selectRaw('partable_type, partable_id, template, details, COUNT(*) AS duplicates_count')
            ->whereNotNull('details')
            ->groupBy('partable_type', 'partable_id', 'template', 'details')
            ->havingRaw('COUNT(*) > 1')
            ->orderBy('partable_type')
            ->orderBy('partable_id')
            ->orderBy('template');

        if ($partableType !== null && $partableType !== '') {
            $query->where('partable_type', $partableType);
        }

        if ($partableId !== null) {
            $query->where('partable_id', $partableId);
        }

        if ($template !== null) {
            $query->where('template', $template->value);
        }

        return $query;
    }

    /**
     * Обрабатывает одну группу дублей.
     *
     * Шаги:
     * 1. Загружает полные записи группы.
     * 2. Выбирает keeper или пропускает конфликт.
     * 3. В apply-режиме удаляет дубли транзакционно.
     */
    private function processGroup(object $group, bool $dryRun, array &$summary): void
    {
        $specifications = PartSpecification::query()
            ->where('partable_type', $group->partable_type)
            ->where('partable_id', (int) $group->partable_id)
            ->where('template', (string) $group->template)
            ->whereRaw('details = CAST(? AS jsonb)', [
                json_encode($group->details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ])
            ->orderBy('id')
            ->get();

        if ($specifications->count() <= 1) {
            return;
        }

        $withFeatureValue = $specifications
            ->filter(fn (PartSpecification $specification): bool => $specification->feature_value_id !== null)
            ->values();

        if ($withFeatureValue->count() > 1) {
            $summary['skipped_conflicts']++;

            return;
        }

        $keeper = $withFeatureValue->first() ?? $specifications->first();
        $duplicates = $specifications
            ->filter(fn (PartSpecification $specification): bool => (int) $specification->id !== (int) $keeper->id)
            ->values();

        if ($duplicates->isEmpty()) {
            return;
        }

        if ($dryRun) {
            $summary['dry_run_planned'] += $duplicates->count();

            return;
        }

        DB::transaction(function () use ($duplicates, &$summary): void {
            PartSpecification::query()
                ->whereKey($duplicates->pluck('id')->all())
                ->delete();

            $summary['removed'] += $duplicates->count();
        });
    }
}
