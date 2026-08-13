<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application;

use App\Modules\Templates\Domain\Contracts\WiperSpecificationServiceInterface;
use App\Modules\Templates\Domain\Enums\Wiper\WiperSideEnum;
use Psr\Log\LoggerInterface;

/**
 * Доменное правило структуры деталей дворников ТС.
 *
 * Дворники хранятся как ОДНА PartSpecification на сторону: `details` содержит ровно один
 * корневой ключ — `front` ИЛИ `back`. Сервис чистый (только массивы): определяет сторону,
 * извлекает/нормализует данные стороны, разбивает legacy-структуру `{front,back}` на стороны
 * и склеивает обратно для экспорта. Источник значений сторон — WiperSideEnum.
 */
final readonly class WiperSpecificationService implements WiperSpecificationServiceInterface
{
    /**
     * Получает optional PSR logger для предупреждений о нарушениях инвариантов.
     * Шаги:
     * 1) Сохраняет logger, если он передан контейнером.
     * 2) Оставляет null допустимым значением, чтобы сервис мог работать как чистый
     *    array-transformer без обязательной инфраструктурной зависимости.
     */
    public function __construct(
        private ?LoggerInterface $logger = null,
    ) {}

    /**
     * Сторона по корневым ключам: front / back / null (нет данных либо присутствуют обе).
     * Шаги:
     * 1) Проверяет наличие корневых ключей `front` и `back`.
     * 2) Возвращает `front`, если есть только передняя сторона.
     * 3) Возвращает `back`, если есть только задняя сторона.
     * 4) Возвращает null для пустой, неизвестной или legacy-смешанной структуры.
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
     * Шаги:
     * 1) Валидирует строку стороны через `WiperSideEnum`.
     * 2) Забирает payload выбранного корневого ключа.
     * 3) Возвращает payload только если это массив; иначе возвращает пустой массив.
     *
     * @param  array<string, mixed>  $details
     */
    public function sideData(array $details, string $side): array
    {
        $sideEnum = WiperSideEnum::tryFrom($side);

        if ($sideEnum === null) {
            return [];
        }

        $sideData = $details[$side] ?? [];

        return is_array($sideData) ? $sideData : [];
    }

    /**
     * Нормализует массив адаптеров к массиву уникальных непустых строк-кодов.
     * Шаги:
     * 1) Отбрасывает null/пустую строку.
     * 2) Приводит значения к string и удаляет дубли.
     * 3) Переиндексирует список.
     *
     * @param  array<int, string|null>  $value
     * @return array<int, string>
     */
    private function normalizeAdapters(array $value): array
    {
        $isFilledAdapter = static fn (?string $item) => $item !== null && $item !== '';
        $toAdapterString = static fn (string $item) => $item;

        $adapters = array_filter($value, $isFilledAdapter);

        return array_values(array_unique(array_map($toAdapterString, $adapters)));
    }

    /**
     * Нормализует адаптеры машины и логирует нарушение инварианта "один адаптер на вариант".
     *
     * Шаги:
     * 1. Приводит входное значение к массиву кодов.
     * 2. Логирует предупреждение, если кодов больше одного.
     * 3. Возвращает нормализованный массив без потери данных.
     */
    private function normalizeVehicleAdapters(?int $partSpecificationId, string $side, array $rawAdapters): array
    {
        $adapters = $this->normalizeAdapters($rawAdapters);

        if (count($adapters) > 1) {
            $this->logger?->warning('В adapter_type_* найдено более одного значения для ТС', [
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
     * Шаги:
     * 1) Если сторона null или неизвестна enum-у — возвращает исходный details-массив.
     * 2) Иначе извлекает данные выбранной стороны через `sideData()`.
     * 3) Возвращает структуру с единственным корневым ключом этой стороны.
     *
     * @param  array<string, mixed>  $details
     * @return array<string, mixed>
     */
    public function sanitizeDetailsForSide(array $details, ?string $side): array
    {
        $sideEnum = $side === null ? null : WiperSideEnum::tryFrom($side);

        if ($sideEnum === null) {
            return $details;
        }

        return [$side => $this->sideData($details, $side)];
    }

    /**
     * Разбивает details на стороны. Для каждой присутствующей стороны с данными — нормализованные
     * детали. Если в одной стороне несколько кодов адаптера — разворачивает в несколько вариантов
     * (по одному коду на запись).
     * Шаги:
     * 1) Проходит по поддержанным сторонам `front`/`back`.
     * 2) Пропускает сторону без данных.
     * 3) Разворачивает сторону по адаптерам через `expandByAdapters()`.
     * 4) Возвращает список структур с одним корневым ключом details на вариант.
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
     * Шаги:
     * 1) Создаёт пустой details-массив.
     * 2) Добавляет `front`, только если передние данные не пустые.
     * 3) Добавляет `back`, только если задние данные не пустые.
     * 4) Возвращает legacy-структуру для export presenter-а.
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
     * Этот метод разворачивает side-details с несколькими адаптерами в варианты по одному
     * адаптеру.
     * Шаги:
     * 1) Нормализует значение adapter-поля в массив кодов.
     * 2) Если адаптеров 0 или 1 — возвращает один вариант с нормализованным массивом адаптеров.
     * 3) Если адаптеров несколько — создаёт отдельный вариант на каждый код.
     *
     * @param  array<string, mixed>  $sideDetails
     * @return array<int, array<string, mixed>>
     */
    private function expandByAdapters(array $sideDetails, string $adapterField): array
    {
        $adapters = $this->normalizeAdapters($sideDetails[$adapterField] ?? []);

        if (count($adapters) <= 1) {
            return [array_merge($sideDetails, [$adapterField => $adapters])];
        }

        $toAdapterSideDetails = static fn (string $adapter) => array_merge($sideDetails, [$adapterField => [$adapter]]);

        return array_map($toAdapterSideDetails, $adapters);
    }
}
