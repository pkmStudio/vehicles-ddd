<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\Wiper;

use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Wiper\WiperAdaptersDTO;
use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Wiper\WiperLengthDTO;
use App\Modules\Applicability\Features\Calculation\Domain\Enums\WiperKitPositionEnum;
use App\Modules\Applicability\Features\Calculation\Domain\ModelData\KitData;

interface WiperDataExtractorInterface
{
    /**
     * Определяет расчетную позицию комплекта дворников.
     *
     * Шаги:
     * 1. Читает positions из wiper details номенклатур комплекта.
     * 2. Отдает приоритет задней позиции, если она найдена.
     * 3. Проверяет front-кандидат на признаки universal комплекта.
     */
    public function extractPosition(KitData $kit): WiperKitPositionEnum;

    /**
     * Извлекает расчетные длины дворников.
     *
     * Шаги:
     * 1. Использует переданную позицию или вычисляет ее по комплекту.
     * 2. Делегирует разбор длины специализированному extractor-у.
     * 3. Возвращает DTO с основным размером, вторым размером и количеством щеток.
     */
    public function extractLength(KitData $kit, ?WiperKitPositionEnum $position = null): WiperLengthDTO;

    /**
     * Извлекает расчетные adapter-наборы дворников.
     *
     * Шаги:
     * 1. Использует переданную позицию или вычисляет ее по комплекту.
     * 2. Делегирует разбор adapter fields специализированному extractor-у.
     * 3. Возвращает DTO с полным и применяемым списками adapters.
     */
    public function extractAdapters(KitData $kit, ?WiperKitPositionEnum $position = null): WiperAdaptersDTO;
}
