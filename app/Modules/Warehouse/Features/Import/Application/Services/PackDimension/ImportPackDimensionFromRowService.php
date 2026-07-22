<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Application\Services\PackDimension;

use App\Modules\Warehouse\Features\Import\Domain\Contracts\Commands\PackDimensionCommandInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Repositories\PackDimensionRepositoryInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Services\PackDimension\ImportPackDimensionFromRowServiceInterface;
use App\Modules\Warehouse\Features\Import\Domain\ModelData\PackDimensionData;
use InvalidArgumentException;

/**
 * Валидирует Excel-строку упаковочного размера и пишет её через явные create/update команды.
 */
final readonly class ImportPackDimensionFromRowService implements ImportPackDimensionFromRowServiceInterface
{
    /**
     * Получает чтение и команду записи упаковочного размера.
     */
    public function __construct(
        private PackDimensionRepositoryInterface $packDimensions,
        private PackDimensionCommandInterface $command,
    ) {}

    /**
     * Валидирует строку и пишет упаковочный размер.
     *
     * @param  array<int, mixed>  $row
     */
    public function importFromRow(array $row): PackDimensionData
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

        $existing = $id === null ? null : $this->packDimensions->findById($id);

        return $existing === null
            ? $this->command->create($data)
            : $this->command->updateById($data);
    }
}
