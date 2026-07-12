<?php

declare(strict_types=1);

namespace App\Vehicles\Templates\Application;

use App\Vehicles\Templates\Domain\Contracts\WiperSpecificationServiceInterface;
use App\Vehicles\Shared\Domain\Enums\Vehicle\WiperSideEnum;
use Illuminate\Support\Facades\Log;

/**
 * Доменное правило структуры деталей дворников ТС.
 *
 * Дворники хранятся как ОДНА PartSpecification на сторону: `details` содержит ровно один
 * корневой ключ — `front` ИЛИ `back`. Сервис чистый (только массивы): определяет сторону,
 * извлекает/нормализует данные стороны, разбивает legacy-структуру `{front,back}` на стороны
 * и склеивает обратно для экспорта. Источник значений сторон — WiperSideEnum (Domain).
 */
final readonly class WiperSpecificationService implements WiperSpecificationServiceInterface
{
    /**
     * Сторона по корневым ключам: front / back / null (нет данных либо присутствуют обе).
     *
     * @param  array<string, mixed>  $details
     */
    public function detectSide(array $details): ?string
    {
        $hasFront = array_key_exists(WiperSideEnum::FRONT->value, $details);
        $hasBack = array_key_exists(WiperSideEnum::BACK->value, $details);

        if ($hasFront && ! $hasBack) {
            return WiperSideEnum::FRONT->value;
        }

        if (! $hasFront && $hasBack) {
            return WiperSideEnum::BACK->value;
        }

        return null;
    }

    /**
     * Данные одной стороны (или пустой массив).
     *
     * @param  array<string, mixed>  $details
     */
    public function sideData(array $details, string $side): array
    {
        if (WiperSideEnum::tryFrom($side) === null) {
            return [];
        }

        $sideData = $details[$side] ?? [];

        return is_array($sideData) ? $sideData : [];
    }

    /**
     * Нормализует значение адаптера к массиву уникальных непустых строк-кодов.
     *
     * @return array<int, string>
     */
    private function normalizeAdapters(mixed $value): array
    {
        if (is_array($value)) {
            $adapters = array_filter($value, static fn ($item) => $item !== null && $item !== '');

            return array_values(array_unique(array_map(static fn ($item) => (string) $item, $adapters)));
        }

        if (is_string($value)) {
            $trimmed = trim($value);

            return $trimmed === '' ? [] : [$trimmed];
        }

        if (is_int($value) || is_float($value) || is_bool($value)) {
            return [(string) $value];
        }

        return [];
    }

    /**
     * Нормализует адаптеры машины и логирует нарушение инварианта "один адаптер на вариант".
     *
     * Шаги:
     * 1. Приводит входное значение к массиву кодов.
     * 2. Логирует предупреждение, если кодов больше одного.
     * 3. Возвращает нормализованный массив без потери данных.
     */
    private function normalizeVehicleAdapters(?int $partSpecificationId, string $side, mixed $rawAdapters): array
    {
        $adapters = $this->normalizeAdapters($rawAdapters);

        if (count($adapters) > 1) {
            Log::warning('В adapter_type_* найдено более одного значения для ТС', [
                'part_specification_id' => $partSpecificationId,
                'side' => $side,
                'adapter_count' => count($adapters),
                'adapters' => $adapters,
            ]);
        }

        return $adapters;
    }

    /**
     * Нормализует details одной стороны.
     *
     * Шаги:
     * 1. Определяет поле адаптера по стороне.
     * 2. Нормализует значение адаптера.
     * 3. Возвращает side-details с адаптером в массивном формате.
     */
    private function normalizeSideDetails(array $sideDetails, ?int $partSpecificationId, string $side): array
    {
        $sideEnum = WiperSideEnum::tryFrom($side);
        if ($sideEnum === null) {
            return $sideDetails;
        }

        $adapterField = $sideEnum->adapterField();
        $sideDetails[$adapterField] = $this->normalizeVehicleAdapters(
            $partSpecificationId,
            $side,
            $sideDetails[$adapterField] ?? [],
        );

        return $sideDetails;
    }

    /**
     * Считает количество адаптеров у details конкретной стороны.
     *
     * Шаги:
     * 1. Валидирует сторону через enum.
     * 2. Достает side-details и поле адаптера.
     * 3. Возвращает количество нормализованных кодов.
     */
    public function getVehicleAdapterCount(array $details, string $side): int
    {
        $sideEnum = WiperSideEnum::tryFrom($side);
        if ($sideEnum === null) {
            return 0;
        }

        $sideData = $this->sideData($details, $side);

        return count($this->normalizeAdapters($sideData[$sideEnum->adapterField()] ?? []));
    }

    /**
     * Оставляет в структуре деталей только выбранную сторону: `[side => sideData]`.
     * Для неизвестной стороны возвращает исходные данные без изменений.
     *
     * @param  array<string, mixed>  $details
     * @return array<string, mixed>
     */
    public function sanitizeDetailsForSide(array $details, ?string $side): array
    {
        if ($side === null || WiperSideEnum::tryFrom($side) === null) {
            return $details;
        }

        return [$side => $this->sideData($details, $side)];
    }

    /**
     * Разбивает details на стороны. Для каждой присутствующей стороны с данными — нормализованные
     * детали. Если в одной стороне несколько кодов адаптера — разворачивает в несколько вариантов
     * (по одному коду на запись).
     *
     * @param  array<string, mixed>  $details
     * @return array<int, array{side: string, details: array<string, mixed>}>
     */
    public function splitDetails(array $details): array
    {
        $result = [];

        foreach (WiperSideEnum::cases() as $sideEnum) {
            $side = $sideEnum->value;
            $sideDetails = $this->sideData($details, $side);
            if ($sideDetails === []) {
                continue;
            }

            foreach ($this->expandByAdapters($sideDetails, $sideEnum->adapterField()) as $variant) {
                $result[] = ['side' => $side, 'details' => [$side => $variant]];
            }
        }

        return $result;
    }

    /**
     * Разбивает сохраненную спецификацию на отдельные side-варианты.
     *
     * Шаги:
     * 1. Читает переданные details.
     * 2. Для каждой стороны нормализует данные.
     * 3. Возвращает варианты с одним корневым ключом details.
     *
     * @return array<int, array{part_specification_id: int, side: string, details: array<string, mixed>}>
     */
    public function splitSpecification(array $details, ?int $partSpecificationId): array
    {
        $result = [];

        foreach (WiperSideEnum::cases() as $sideEnum) {
            $side = $sideEnum->value;
            $sideDetails = $this->sideData($details, $side);
            if ($sideDetails === []) {
                continue;
            }

            $normalizedDetails = $this->normalizeSideDetails($sideDetails, $partSpecificationId, $side);
            foreach ($this->expandByAdapters($normalizedDetails, $sideEnum->adapterField()) as $variant) {
                $result[] = [
                    'part_specification_id' => (int) $partSpecificationId,
                    'side' => $side,
                    'details' => [$side => $variant],
                ];
            }
        }

        return $result;
    }

    /**
     * Склеивает раздельные стороны обратно в `{front, back}` для экспорта (старый формат).
     *
     * @param  array<string, mixed>  $frontData
     * @param  array<string, mixed>  $backData
     * @return array<string, mixed>
     */
    public function mergeForExport(array $frontData, array $backData): array
    {
        $merged = [];

        if ($frontData !== []) {
            $merged[WiperSideEnum::FRONT->value] = $frontData;
        }

        if ($backData !== []) {
            $merged[WiperSideEnum::BACK->value] = $backData;
        }

        return $merged;
    }

    /**
     * @param  array<string, mixed>  $sideDetails
     * @return array<int, array<string, mixed>>
     */
    private function expandByAdapters(array $sideDetails, string $adapterField): array
    {
        $adapters = $this->normalizeAdapters($sideDetails[$adapterField] ?? []);

        if (count($adapters) <= 1) {
            return [array_merge($sideDetails, [$adapterField => $adapters])];
        }

        return array_map(
            static fn (string $adapter) => array_merge($sideDetails, [$adapterField => [$adapter]]),
            $adapters,
        );
    }
}
