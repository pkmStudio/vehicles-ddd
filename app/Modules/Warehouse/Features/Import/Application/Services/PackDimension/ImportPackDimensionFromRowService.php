<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Application\Services\PackDimension;

use App\Modules\Warehouse\Features\Import\Domain\Contracts\Commands\PackDimensionCommandInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Repositories\PackDimensionRepositoryInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Repositories\TypeRepositoryInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Services\PackDimension\ImportPackDimensionFromRowServiceInterface;
use App\Modules\Warehouse\Features\Import\Domain\DTOs\PackDimension\PackDimensionImportRowDTO;
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
     * Шаги:
     * 1) Сохранить repository упаковочных размеров для id lookup.
     * 2) Сохранить repository типов для резолва type из Excel-ячейки.
     * 3) Сохранить command port для create/update записи упаковки.
     */
    public function __construct(
        private PackDimensionRepositoryInterface $packDimensions,
        private TypeRepositoryInterface $types,
        private PackDimensionCommandInterface $command,
    ) {}

    /**
     * Валидирует строку и пишет упаковочный размер.
     * Шаги:
     * 1) Прочитать optional id и scalar поля упаковки из Excel-строки.
     * 2) Зарезолвить type по id, названию или коду справочника.
     * 3) Запретить пустое название коробки.
     * 4) Запретить нулевые/отрицательные габариты и вес.
     * 5) Запретить отрицательную цену.
     * 6) Собрать PackDimensionData.
     * 7) Если id найден в базе, обновить запись; иначе создать новую.
     *
     */
    public function importFromRow(PackDimensionImportRowDTO $row): PackDimensionData
    {
        $type = $this->resolveType($row->type);

        $data = new PackDimensionData(
            name: $row->name,
            weight: $row->weight,
            width: $row->width,
            height: $row->height,
            length: $row->length,
            price: $row->price,
            typeId: (int) $type->id,
            id: $row->id,
        );

        $existing = $row->id === null ? null : $this->packDimensions->findById($row->id);

        return $existing === null
            ? $this->command->create($data)
            : $this->command->updateById($data);
    }

    /**
     * Резолвит пользовательское значение типа товара по названию или коду; numeric id оставлен
     * для совместимости со старыми файлами.
     * Шаги:
     * 1) Нормализовать raw value из Excel-ячейки.
     * 2) Отклонить пустую ячейку как обязательное поле.
     * 3) Сравнить raw value с id типа для legacy-файлов.
     * 4) Сравнить normalized value с name типа.
     * 5) Сравнить normalized value с char-кодом типа, если он есть.
     * 6) Если совпадений нет, выбросить validation error со ссылкой на лист справочников.
     */
    private function resolveType(string $raw): TypeData
    {
        if ($raw === '') {
            throw ImportRowValidationException::withMessage(
                'Тип товара обязателен. Укажите название или код из листа «Справочники».',
            );
        }

        $normalized = mb_strtoupper($raw);

        foreach ($this->types->all() as $type) {
            if ($type->id !== null && $raw === (string) $type->id) {
                return $type;
            }

            if (mb_strtoupper(trim($type->name)) === $normalized) {
                return $type;
            }

            if ($type->char !== null && mb_strtoupper(trim($type->char)) === $normalized) {
                return $type;
            }
        }

        throw ImportRowValidationException::withMessage(
            "Тип товара «{$raw}» не найден. Укажите название или код из листа «Справочники».",
        );
    }
}
