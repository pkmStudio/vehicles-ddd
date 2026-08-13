<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\Contracts;

interface WiperSpecificationServiceInterface
{
    /**
     * Этот метод должен определить единственную сторону details по корневым ключам.
     * Шаги:
     * 1) Проверить структуру details на `front` и `back`.
     * 2) Вернуть сторону только если она однозначна; иначе вернуть null.
     *
     * @param  array<string, mixed>  $details
     */
    public function detectSide(array $details): ?string;

    /**
     * Этот метод должен вернуть payload выбранной стороны.
     * Шаги:
     * 1) Проверить, что сторона поддержана.
     * 2) Вернуть массив side-details или пустой массив.
     *
     * @param  array<string, mixed>  $details
     * @return array<string, mixed>
     */
    public function sideData(array $details, string $side): array;

    /**
     * Этот метод должен посчитать адаптеры выбранной стороны vehicle details.
     * Шаги:
     * 1) Найти adapter field по стороне.
     * 2) Нормализовать значение adapter field.
     * 3) Вернуть количество кодов.
     *
     * @param  array<string, mixed>  $details
     */
    public function getVehicleAdapterCount(array $details, string $side): int;

    /**
     * Этот метод должен оставить в details только выбранную сторону.
     * Шаги:
     * 1) Если сторона неизвестна — вернуть исходный details-массив.
     * 2) Иначе вернуть структуру `[side => sideData]`.
     *
     * @param  array<string, mixed>  $details
     * @return array<string, mixed>
     */
    public function sanitizeDetailsForSide(array $details, ?string $side): array;

    /**
     * Этот метод должен разбить legacy `{front, back}` details на side-варианты.
     * Шаги:
     * 1) Обойти поддержанные стороны.
     * 2) Пропустить пустые стороны.
     * 3) Развернуть сторону по адаптерам.
     *
     * @param  array<string, mixed>  $details
     * @return array<int, array{side: string, details: array<string, mixed>}>
     */
    public function splitDetails(array $details): array;

    /**
     * Этот метод должен разбить сохранённую спецификацию дворников на side-варианты.
     * Шаги:
     * 1) Обойти поддержанные стороны.
     * 2) Нормализовать side-details и adapter field.
     * 3) Вернуть варианты с `part_specification_id`.
     *
     * @param  array<string, mixed>  $details
     * @return array<int, array{part_specification_id: int, side: string, details: array<string, mixed>}>
     */
    public function splitSpecification(array $details, ?int $partSpecificationId): array;

    /**
     * Этот метод должен собрать legacy export details из раздельных сторон.
     * Шаги:
     * 1) Добавить непустой front payload.
     * 2) Добавить непустой back payload.
     * 3) Вернуть details-массив без пустых сторон.
     *
     * @param  array<string, mixed>  $frontData
     * @param  array<string, mixed>  $backData
     * @return array<string, mixed>
     */
    public function mergeForExport(array $frontData, array $backData): array;
}
