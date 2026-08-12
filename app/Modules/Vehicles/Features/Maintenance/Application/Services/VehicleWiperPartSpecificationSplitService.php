<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Maintenance\Application\Services;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Templates\Domain\Enums\Wiper\WiperSideEnum;
use App\Modules\Vehicles\Features\Maintenance\Domain\Contracts\Clients\TemplatesClientInterface;
use App\Modules\Vehicles\Features\Maintenance\Infrastructure\Models\PartSpecification;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use Illuminate\Support\Facades\Log;

/**
 * Сервис миграции legacy-структуры дворников в отдельные PartSpecification по сторонам.
 */
final readonly class VehicleWiperPartSpecificationSplitService
{
    /**
     * Получает client Templates для разрезания details дворников по сторонам.
     *
     * Шаги:
     * 1. Принять feature-local Templates client port.
     * 2. Сохранить dependency для последующего split legacy wiper details.
     */
    public function __construct(
        private TemplatesClientInterface $templates,
    ) {}

    /**
     * Разделяет combined/multi-adapter записи дворников.
     *
     * Шаги:
     * 1. Находит vehicle wiper спецификации с front/back ключами.
     * 2. Для combined и multi-adapter записей создает отдельные side-варианты.
     * 3. В apply-режиме удаляет исходную запись после создания/reuse целей.
     *
     * @return array<string, int>
     */
    public function split(bool $dryRun = false, int $chunk = 200): array
    {
        $summary = $this->emptySummary();

        PartSpecification::query()
            ->where('partable_type', PartableTypeEnum::VEHICLE->value)
            ->where('template', DetailTemplateEnum::WIPER->value)
            ->where(function ($query): void {
                $query
                    ->whereRaw(sprintf("jsonb_exists(details, '%s')", WiperSideEnum::FRONT->value))
                    ->orWhereRaw(sprintf("jsonb_exists(details, '%s')", WiperSideEnum::BACK->value));
            })
            ->orderBy('id')
            ->chunkById(max(1, $chunk), function ($specifications) use (&$summary, $dryRun): void {
                foreach ($specifications as $specification) {
                    $this->processSpecification($specification, $dryRun, $summary);
                }
            });

        return $summary;
    }

    /**
     * Обрабатывает одну PartSpecification.
     *
     * Шаги:
     * 1. Удаляет или чистит пустые стороны.
     * 2. Проверяет, требуется ли split.
     * 3. Создает/reuse side-варианты и удаляет source.
     */
    private function processSpecification(PartSpecification $specification, bool $dryRun, array &$summary): void
    {
        if ($this->cleanupEmptySides($specification, $dryRun, $summary)) {
            return;
        }

        $entries = $this->templates->splitVehicleWiperSpecification((array) ($specification->details ?? []), (int) $specification->id);
        if (! $this->shouldSplit($entries)) {
            return;
        }

        $summary['found']++;
        $summary['processed']++;

        $targetIds = [];
        foreach ($entries as $entry) {
            $details = (array) $entry['details'];
            if ($this->isDetailsEmpty($details)) {
                $summary['skipped']++;

                continue;
            }

            $targetId = $this->resolveTarget($specification, $details, $dryRun, $summary);
            if ($targetId !== null) {
                $targetIds[] = $targetId;
            }
        }

        if ($dryRun || $targetIds === []) {
            return;
        }

        $specification->delete();
        $summary['removed']++;
    }

    /**
     * Определяет, нужно ли разбивать запись.
     *
     * Шаги:
     * 1. Считает количество side-вариантов.
     * 2. Если вариант один, split не нужен.
     * 3. Если вариантов несколько, запись считается legacy/мульти-адаптерной.
     */
    private function shouldSplit(array $entries): bool
    {
        return count($entries) > 1;
    }

    /**
     * Находит или создает целевую side-запись.
     *
     * Шаги:
     * 1. Ищет точное совпадение details у той же машины.
     * 2. В dry-run режиме только считает планируемое создание.
     * 3. В apply-режиме создает новую запись с метаданными source.
     */
    private function resolveTarget(PartSpecification $source, array $details, bool $dryRun, array &$summary): ?int
    {
        $existing = PartSpecification::query()
            ->where('partable_type', PartableTypeEnum::VEHICLE->value)
            ->where('partable_id', (int) $source->partable_id)
            ->where('template', DetailTemplateEnum::WIPER->value)
            ->whereKeyNot((int) $source->id)
            ->whereRaw('details = CAST(? AS jsonb)', [
                json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ])
            ->orderBy('id')
            ->first();

        if ($existing !== null) {
            return (int) $existing->id;
        }

        if ($dryRun) {
            $summary['created']++;

            return null;
        }

        $created = PartSpecification::query()->create([
            'partable_type' => PartableTypeEnum::VEHICLE->value,
            'partable_id' => (int) $source->partable_id,
            'feature_value_id' => $source->feature_value_id,
            'template' => DetailTemplateEnum::WIPER,
            'name' => $source->name,
            'text' => $source->text,
            'details' => $details,
        ]);

        $summary['created']++;

        return (int) $created->id;
    }

    /**
     * Удаляет пустую сторону или пустую одностороннюю запись.
     *
     * Шаги:
     * 1. Определяет наличие front/back.
     * 2. Удаляет полностью пустую single-side запись.
     * 3. Убирает пустую сторону из mixed-записи.
     */
    private function cleanupEmptySides(PartSpecification $specification, bool $dryRun, array &$summary): bool
    {
        $details = (array) ($specification->details ?? []);
        $frontSide = WiperSideEnum::FRONT->value;
        $backSide = WiperSideEnum::BACK->value;
        $hasFront = array_key_exists($frontSide, $details);
        $hasBack = array_key_exists($backSide, $details);

        if (! $hasFront && ! $hasBack) {
            return false;
        }

        $frontEmpty = $hasFront && $this->isDetailsEmpty($details[$frontSide]);
        $backEmpty = $hasBack && $this->isDetailsEmpty($details[$backSide]);

        if (($hasFront xor $hasBack) && ($frontEmpty || $backEmpty)) {
            $summary['processed']++;
            $summary['skipped']++;

            if (! $dryRun) {
                $specification->delete();
                $summary['removed']++;
            }

            return true;
        }

        if ($hasFront && $hasBack && $frontEmpty && $backEmpty) {
            $summary['processed']++;
            $summary['skipped']++;
            Log::warning('PartSpecification дворников содержит две пустые стороны', [
                'part_specification_id' => (int) $specification->id,
            ]);

            if (! $dryRun) {
                $specification->delete();
                $summary['removed']++;
            }

            return true;
        }

        if ($hasFront && $hasBack && $frontEmpty !== $backEmpty) {
            unset($details[$frontEmpty ? $frontSide : $backSide]);
            $specification->details = $details;

            if (! $dryRun) {
                $specification->save();
            }

            if ($this->shouldSplit($this->templates->splitVehicleWiperSpecification($details, (int) $specification->id))) {
                return false;
            }

            $summary['processed']++;

            return true;
        }

        return false;
    }

    /**
     * Проверяет details на пустоту.
     *
     * Шаги:
     * 1. Рекурсивно проходит по массивам.
     * 2. Пустыми считает null, пустую строку и пустой массив.
     * 3. Любое другое значение делает details непустыми.
     */
    private function isDetailsEmpty(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (is_array($value)) {
            foreach ($value as $inner) {
                if (! $this->isDetailsEmpty($inner)) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Возвращает начальный summary.
     *
     * Шаги:
     * 1. Инициализирует все счетчики.
     * 2. Возвращает массив для мутабельного накопления.
     */
    private function emptySummary(): array
    {
        return [
            'found' => 0,
            'processed' => 0,
            'created' => 0,
            'removed' => 0,
            'skipped' => 0,
        ];
    }
}
