<?php

declare(strict_types=1);

namespace App\Warehouse\Import\Application\Services\PackDimension;

use App\Warehouse\Import\Domain\Contracts\Commands\PackDimensionCommandInterface;
use App\Warehouse\Import\Domain\Contracts\Services\PackDimension\UpsertPackDimensionFromRowServiceInterface;
use App\Warehouse\Import\Domain\ModelData\PackDimensionData;
use InvalidArgumentException;

/**
 * Валидирует Excel-строку упаковочного размера и пишет её через Command. Правила валидации 1:1
 * повторяют `PakDimensionsImport::mapRowToDto()` из dan-center.
 */
final readonly class UpsertPackDimensionFromRowService implements UpsertPackDimensionFromRowServiceInterface
{
    public function __construct(
        private PackDimensionCommandInterface $command,
    ) {}

    /**
     * Этот метод валидирует строку и записывает упаковочный размер.
     *
     * Шаги:
     * 1) Распарсить и привести к типам все колонки строки.
     * 2) Проверить бизнес-правила (непустое название, положительные габариты/вес, type_id > 0).
     * 3) Собрать PackDimensionData и записать через Command (update по id либо create).
     *
     * @param  array<int, mixed>  $row
     */
    public function upsertFromRow(array $row): PackDimensionData
    {
        $idCell = isset($row[0]) ? trim((string) $row[0]) : '';
        $id = is_numeric($idCell) ? (int) $idCell : null;

        $name = trim((string) ($row[1] ?? ''));
        $weight = (int) ($row[2] ?? 0);
        $width = (int) ($row[3] ?? 0);
        $height = (int) ($row[4] ?? 0);
        $length = (int) ($row[5] ?? 0);
        $price = (int) ($row[6] ?? 0);
        $typeId = (int) ($row[7] ?? 0);

        if ($name === '') {
            throw new InvalidArgumentException('Пустое название коробки');
        }

        if ($weight <= 0 || $width <= 0 || $height <= 0 || $length <= 0) {
            throw new InvalidArgumentException('Габариты и вес должны быть больше нуля');
        }

        if ($price < 0) {
            throw new InvalidArgumentException('Цена коробки не может быть отрицательной');
        }

        if ($typeId <= 0) {
            throw new InvalidArgumentException('type_id должен быть положительным числом');
        }

        $data = new PackDimensionData(
            name: $name,
            weight: $weight,
            width: $width,
            height: $height,
            length: $length,
            price: $price,
            typeId: $typeId,
            id: $id,
        );

        return $this->command->upsertById($data);
    }
}
