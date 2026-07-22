<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Packaging\Application\Services\Strategies;

use App\Modules\Warehouse\Features\Packaging\Domain\Contracts\Commands\PackDimensionCommandInterface;
use App\Modules\Warehouse\Features\Packaging\Domain\DTOs\PackagingBoxRequirementDTO;
use App\Modules\Warehouse\Features\Packaging\Domain\ModelData\PackDimensionData;
use App\Modules\Warehouse\Features\Packaging\Domain\ModelData\TypeData;
use Illuminate\Support\Collection;

/**
 * Общий алгоритм подбора/создания упаковки: min-объём-first перебор существующих коробок, порог
 * допустимого зазора (`oversizeLimit`), создание новой коробки через Command при отсутствии
 * подходящей. Используется стратегиями, которые считают требуемые габариты из `details` товара
 * (не всеми — некоторые типы подбирают коробку по другим правилам, см. `PackagingService`).
 */
abstract readonly class AbstractPackagingStrategy
{
    /**
     * Получает команду создания новой упаковки.
     */
    public function __construct(
        protected PackDimensionCommandInterface $command,
    ) {}

    /**
     * Этот метод проверяет, помещается ли товар в коробку по трём измерениям.
     * Шаги:
     * 1) Отсортировать обе тройки размеров по возрастанию (не важно, какая ось товара соответствует
     *    какой оси коробки — достаточно совпадения по объёму габаритов).
     * 2) Сравнить поэлементно: каждое измерение товара должно быть не больше соответствующего
     *    измерения коробки.
     *
     * @param  array{0: float, 1: float, 2: float}  $item
     * @param  array{0: float, 1: float, 2: float}  $box
     */
    protected function canFit(array $item, array $box): bool
    {
        sort($item);
        sort($box);

        return $item[0] <= $box[0] && $item[1] <= $box[1] && $item[2] <= $box[2];
    }

    /**
     * Этот метод возвращает максимальное значение из массива измерений одного товара.
     *
     * @param  array<int, float>  $values
     */
    protected function getMaxValue(array $values): float
    {
        return count($values) === 1 ? (float) $values[0] : (float) max($values);
    }

    /**
     * Этот метод подбирает существующую коробку либо создаёт новую под требуемые габариты.
     *
     * Шаги:
     * 1) Отсортировать коробки по объёму по возрастанию и найти первую, куда товар помещается.
     * 2) Если найдена — проверить, не слишком ли она большая (зазор больше oversizeLimit по любой
     *    оси): такую коробку отбраковываем, а не используем "на глазок".
     * 3) Если подходящей коробки нет — создать новую с размерами (требуемый размер + oversizeLimit),
     *    вернуть её.
     *
     * @param  Collection<int, PackDimensionData>  $packDimensions
     */
    protected function calculatePackDimension(
        TypeData $type,
        string $name,
        PackagingBoxRequirementDTO $dto,
        Collection $packDimensions,
        int $oversizeLimit = 5,
    ): PackDimensionData {
        $itemDimensions = [$dto->length, $dto->width, $dto->height];
        $boxVolume = fn (PackDimensionData $box): int => $box->length * $box->width * $box->height;
        $boxCanFit = fn (PackDimensionData $box): bool => $this->canFit(
            item: $itemDimensions,
            box: [$box->length, $box->width, $box->height],
        );

        $suitableBox = $packDimensions
            ->sortBy($boxVolume)
            ->first($boxCanFit);

        if ($suitableBox !== null) {
            $oversize = ($suitableBox->length - $dto->length) > $oversizeLimit
                || ($suitableBox->width - $dto->width) > $oversizeLimit
                || ($suitableBox->height - $dto->height) > $oversizeLimit;

            if ($oversize) {
                $suitableBox = null;
            }
        }

        if ($suitableBox !== null) {
            return $suitableBox;
        }

        $generatedName = sprintf(
            'Упаковка для %s (д x ш x в) %s x %s x %s',
            $name,
            $dto->length,
            $dto->width,
            $dto->height,
        );

        $packDimension = new PackDimensionData(
            name: $generatedName,
            weight: $dto->weight,
            width: (int) ceil($dto->width + $oversizeLimit),
            height: (int) ceil($dto->height + $oversizeLimit),
            length: (int) ceil($dto->length + $oversizeLimit),
            price: 5,
            typeId: $type->id ?? throw new \InvalidArgumentException('TypeData::$id обязателен для создания упаковки'),
            generated: true,
        );

        return $this->command->create($packDimension);
    }
}
