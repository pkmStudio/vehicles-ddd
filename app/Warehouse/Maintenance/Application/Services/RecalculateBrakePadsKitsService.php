<?php

declare(strict_types=1);

namespace App\Warehouse\Maintenance\Application\Services;

use App\Warehouse\KitProperties\Domain\Contracts\Services\KitPropertiesServiceInterface;
use App\Warehouse\KitProperties\Domain\ModelData\NomenclatureData as KitPropertiesNomenclatureData;
use App\Warehouse\KitProperties\Domain\ModelData\TypeData as KitPropertiesTypeData;
use App\Warehouse\Maintenance\Infrastructure\Models\Kit;
use App\Warehouse\Maintenance\Infrastructure\Models\Nomenclature;
use App\Warehouse\Maintenance\Infrastructure\Models\Type;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Пересчитывает производные свойства (вес, упаковку, комплектацию) уже существующих наборов
 * колодок. Порт из dan-center `RecalculateBrakePadsKitsCommand`, но расчёт делегирован
 * `KitPropertiesServiceInterface` вместо старого `KitService::prepareProperties()`.
 */
final readonly class RecalculateBrakePadsKitsService
{
    private const int GUARANTEE_MONTHS = 12;

    /**
     * Получает сервис расчёта производных свойств набора.
     */
    public function __construct(
        private KitPropertiesServiceInterface $kitProperties,
    ) {}

    /**
     * Этот метод перебирает наборы колодок чанками и пересчитывает каждый через KitProperties.
     *
     * Обёртка try/catch на каждый набор не даёт одному битому набору оборвать весь прогон —
     * так же, как это было устроено в dan-center. Важно: `KitPropertiesServiceInterface::build()`
     * не полностью свободен от побочных эффектов даже в dry-run — стратегии подбора упаковки могут
     * создать новую запись `PackDimension`, если подходящей ещё нет (унаследовано от того же
     * поведения в dan-center `KitService::prepareProperties()`).
     *
     * @return array{updated: int, unchanged: int, failed: int}
     */
    public function recalculate(bool $dryRun = false, int $chunk = 200): array
    {
        $type = Type::query()->where('char', 'BP')->first();

        if ($type === null) {
            return ['updated' => 0, 'unchanged' => 0, 'failed' => 0];
        }

        $updated = 0;
        $unchanged = 0;
        $failed = 0;

        Kit::query()
            ->where('type_id', $type->id)
            ->with('nomenclatures.type')
            ->orderBy('id')
            ->chunkById($chunk, function ($kits) use ($dryRun, &$updated, &$unchanged, &$failed): void {
                foreach ($kits as $kit) {
                    try {
                        if ($this->recalculateKit($kit, $dryRun)) {
                            $updated++;
                        } else {
                            $unchanged++;
                        }
                    } catch (Throwable $exception) {
                        $failed++;
                        Log::warning('RecalculateBrakePadsKitsService: failed to recalculate kit', [
                            'id' => $kit->id,
                            'error' => $exception->getMessage(),
                        ]);
                    }
                }
            });

        return ['updated' => $updated, 'unchanged' => $unchanged, 'failed' => $failed];
    }

    /**
     * Пересчитывает один набор и возвращает признак найденных изменений.
     */
    private function recalculateKit(Kit $kit, bool $dryRun): bool
    {
        $nomenclatures = $kit->nomenclatures;

        if ($nomenclatures->isEmpty()) {
            throw new RuntimeException("Набор #{$kit->id} не содержит номенклатур");
        }

        $properties = $this->kitProperties->build(
            $nomenclatures
                ->map(fn (Nomenclature $nomenclature): KitPropertiesNomenclatureData => $this->toKitPropertiesNomenclature($nomenclature))
                ->all(),
        );

        $weight = (int) round($properties->weight);
        $hasChanges = $kit->pack_dimension_id !== $properties->packDimensionId
            || (int) $kit->weight !== $weight;

        if (! $hasChanges) {
            return false;
        }

        if ($dryRun) {
            return true;
        }

        if ($properties->packDimensionId === null) {
            throw new RuntimeException("Невозможно рассчитать упаковку для набора #{$kit->id} (смешанный комплект)");
        }

        $kit->update([
            'complectation' => $properties->complectation,
            'guarantee' => self::GUARANTEE_MONTHS,
            'quantity_in_package' => $properties->quantityInPackage,
            'quantity_package' => $properties->quantityPackage,
            'complement' => $nomenclatures->count() > 1,
            'weight' => $weight,
            'pack_dimension_id' => $properties->packDimensionId,
            'type_id' => $properties->typeId,
            'import_hash' => $properties->importHash,
        ]);

        return true;
    }

    /**
     * Переводит Maintenance-модель номенклатуры в DTO фичи KitProperties.
     */
    private function toKitPropertiesNomenclature(Nomenclature $nomenclature): KitPropertiesNomenclatureData
    {
        $type = $nomenclature->type === null
            ? null
            : new KitPropertiesTypeData(
                name: $nomenclature->type->name,
                char: $nomenclature->type->char,
                id: $nomenclature->type->id,
            );

        return new KitPropertiesNomenclatureData(
            typeId: $nomenclature->type_id,
            partNumber: $nomenclature->part_number,
            quantityInPak: $nomenclature->quantity_in_pak,
            quantityPak: $nomenclature->quantity_pak,
            weight: $nomenclature->weight,
            material: $nomenclature->material,
            details: $nomenclature->details,
            id: $nomenclature->id,
            type: $type,
        );
    }
}
