<?php

declare(strict_types=1);

namespace App\Warehouse\Maintenance\Application\Services;

use App\Warehouse\Maintenance\Infrastructure\Models\PackDimension;
use App\Warehouse\Maintenance\Infrastructure\Models\Type;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Удаляет неиспользуемые упаковочные размеры колодок (`pack_dimensions` типа BrakePads без единого
 * набора). Порт из dan-center `CleanupBrakePadsPakDimensionsCommand` — логика вынесена в сервис,
 * команда остаётся тонкой.
 */
final readonly class CleanupBrakePadsPackDimensionsService
{
    /**
     * Этот метод находит и опционально удаляет неиспользуемые коробки колодок.
     *
     * Шаги:
     * 1) Резолвить тип "Колодки" по стабильному char — если типа ещё нет, нечего чистить.
     * 2) Выбрать кандидатов: коробки этого типа без единого набора (`kits`).
     * 3) В dry-run — вернуть кандидатов без удаления.
     * 4) В боевом режиме — повторно проверить занятость каждого кандидата перед delete (защита от
     *    гонки с параллельным импортом/пересчётом наборов) и удалить только по-прежнему свободные.
     *
     * @return array{candidates: Collection<int, PackDimension>, deleted: int, skipped: int}
     */
    public function cleanup(bool $dryRun = false): array
    {
        $type = Type::query()->where('char', 'BP')->first();

        if ($type === null) {
            $candidates = new Collection;

            return ['candidates' => $candidates, 'deleted' => 0, 'skipped' => 0];
        }

        $candidates = PackDimension::query()
            ->where('type_id', $type->id)
            ->whereDoesntHave('kits')
            ->orderBy('id')
            ->get(['id', 'name']);

        if ($dryRun) {
            return ['candidates' => $candidates, 'deleted' => 0, 'skipped' => 0];
        }

        $deleted = 0;
        $skipped = 0;

        foreach ($candidates as $candidate) {
            $stillUnused = PackDimension::query()
                ->whereKey($candidate->id)
                ->whereDoesntHave('kits')
                ->exists();

            if (! $stillUnused) {
                $skipped++;
                Log::warning(
                    message: 'CleanupBrakePadsPackDimensionsService: skipped busy record',
                    context: [
                        'id' => $candidate->id,
                    ],
                );

                continue;
            }

            PackDimension::query()->whereKey($candidate->id)->delete();
            $deleted++;
        }

        return ['candidates' => $candidates, 'deleted' => $deleted, 'skipped' => $skipped];
    }
}
