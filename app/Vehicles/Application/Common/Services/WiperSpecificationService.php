<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Common\Services;

use App\Vehicles\Domain\Contracts\Application\Common\Services\WiperSpecificationServiceInterface;

/**
 * Доменное правило структуры деталей дворников ТС.
 *
 * Дворники хранятся как ОДНА PartSpecification на сторону: `details` содержит ровно один
 * корневой ключ — `front` ИЛИ `back`. Сервис чистый (только массивы): определяет сторону,
 * извлекает/нормализует данные стороны, разбивает legacy-структуру `{front,back}` на стороны
 * и склеивает обратно для экспорта.
 */
final readonly class WiperSpecificationService implements WiperSpecificationServiceInterface
{
    public const string SIDE_FRONT = 'front';

    public const string SIDE_BACK = 'back';

    private const array ADAPTER_FIELD = [
        self::SIDE_FRONT => 'adapter_type_front',
        self::SIDE_BACK => 'adapter_type_rear',
    ];

    /**
     * Сторона по корневым ключам: front / back / null (нет данных либо присутствуют обе).
     *
     * @param  array<string, mixed>  $details
     */
    public function detectSide(array $details): ?string
    {
        $hasFront = array_key_exists(self::SIDE_FRONT, $details);
        $hasBack = array_key_exists(self::SIDE_BACK, $details);

        if ($hasFront && ! $hasBack) {
            return self::SIDE_FRONT;
        }

        if (! $hasFront && $hasBack) {
            return self::SIDE_BACK;
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
        if (! $this->isSide($side)) {
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
    public function normalizeAdapters(mixed $value): array
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
     * Оставляет в структуре деталей только выбранную сторону: `[side => sideData]`.
     * Для неизвестной стороны возвращает исходные данные без изменений.
     *
     * @param  array<string, mixed>  $details
     * @return array<string, mixed>
     */
    public function sanitizeDetailsForSide(array $details, ?string $side): array
    {
        if (! $this->isSide($side)) {
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

        foreach ([self::SIDE_FRONT, self::SIDE_BACK] as $side) {
            $sideDetails = $this->sideData($details, $side);
            if ($sideDetails === []) {
                continue;
            }

            $adapterField = self::ADAPTER_FIELD[$side];
            foreach ($this->expandByAdapters($sideDetails, $adapterField) as $variant) {
                $result[] = ['side' => $side, 'details' => [$side => $variant]];
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
            $merged[self::SIDE_FRONT] = $frontData;
        }

        if ($backData !== []) {
            $merged[self::SIDE_BACK] = $backData;
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

    private function isSide(?string $side): bool
    {
        return $side === self::SIDE_FRONT || $side === self::SIDE_BACK;
    }
}
