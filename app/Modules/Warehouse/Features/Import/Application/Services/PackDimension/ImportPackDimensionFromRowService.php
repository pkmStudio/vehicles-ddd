<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Application\Services\PackDimension;

use App\Modules\Warehouse\Features\Import\Domain\Contracts\Commands\PackDimensionCommandInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Repositories\PackDimensionRepositoryInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Repositories\TypeRepositoryInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Services\PackDimension\ImportPackDimensionFromRowServiceInterface;
use App\Modules\Warehouse\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Warehouse\Features\Import\Domain\ModelData\PackDimensionData;
use App\Modules\Warehouse\Features\Import\Domain\ModelData\TypeData;

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
        private TypeRepositoryInterface $types,
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
        $type = $this->resolveType($row[7] ?? null);

        if ($name === '') {
            throw ImportRowValidationException::withMessage('Пустое название коробки');
        }

        if ($weight <= 0 || $width <= 0 || $height <= 0 || $length <= 0) {
            throw ImportRowValidationException::withMessage('Габариты и вес должны быть больше нуля');
        }

        if ($price < 0) {
            throw ImportRowValidationException::withMessage('Цена коробки не может быть отрицательной');
        }

        $data = new PackDimensionData(
            name: $name,
            weight: $weight,
            width: $width,
            height: $height,
            length: $length,
            price: $price,
            typeId: (int) $type->id,
            id: $id,
        );

        $existing = $id === null ? null : $this->packDimensions->findById($id);

        return $existing === null
            ? $this->command->create($data)
            : $this->command->updateById($data);
    }

    /**
     * Резолвит пользовательское значение типа товара по названию или коду; numeric id оставлен
     * для совместимости со старыми файлами.
     */
    private function resolveType(mixed $value): TypeData
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            throw ImportRowValidationException::withMessage(
                'Тип товара обязателен. Укажите название или код из листа «Справочники».',
            );
        }

        $normalized = $this->normalizeTypeValue($raw);

        foreach ($this->types->all() as $type) {
            if ($type->id !== null && $raw === (string) $type->id) {
                return $type;
            }

            if ($this->normalizeTypeValue($type->name) === $normalized) {
                return $type;
            }

            if ($type->char !== null && $this->normalizeTypeValue($type->char) === $normalized) {
                return $type;
            }
        }

        throw ImportRowValidationException::withMessage(
            "Тип товара «{$raw}» не найден. Укажите название или код из листа «Справочники».",
        );
    }

    private function normalizeTypeValue(string $value): string
    {
        return mb_strtoupper(trim($value));
    }
}
