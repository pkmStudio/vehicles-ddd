<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Services\Presenters;

use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;
use InvalidArgumentException;

/**
 * @template TData of AbstractDetailsData
 */
abstract readonly class AbstractDetailsPresenter
{
    /**
     * Этот метод возвращает Excel-заголовки details-шаблона.
     * Шаги:
     * 1) Конкретный presenter перечисляет колонки в том порядке, в котором `cells()` вернёт
     *    значения.
     *
     * @return array<int, mixed>
     */
    abstract public function headings(): array;

    /**
     * Этот метод возвращает Data-класс, который presenter умеет рендерить.
     * Шаги:
     * 1) Конкретный presenter указывает class-string своего `*DetailsData`.
     * 2) Базовый класс использует его для `::from($details)` и runtime-проверки типа.
     *
     * @return class-string<TData>
     */
    abstract protected function dataClass(): string;

    /**
     * Этот метод превращает типизированный details-объект в Excel-ячейки.
     * Шаги:
     * 1) Конкретный presenter проверяет ожидаемый Data-класс через `ensureData()`.
     * 2) Возвращает значения в том же порядке, что и `headings()`.
     *
     * @param  TData  $data
     * @return array<int, mixed>
     */
    abstract public function cells(AbstractDetailsData $data): array;

    /**
     * Этот метод рендерит plain details-массив в Excel-ячейки через типизированный Data-объект.
     * Шаги:
     * 1) Собирает Data-объект из массива через `dataFrom()`.
     * 2) Передаёт типизированный объект в `cells()`.
     *
     * @param  array<string, mixed>  $details
     * @return array<int, mixed>
     */
    public function cellsFromDetails(array $details): array
    {
        return $this->cells($this->dataFrom($details));
    }

    /**
     * Этот метод восстанавливает конкретный `*DetailsData` из сохранённого details JSON.
     * Шаги:
     * 1) Берёт class-string из `dataClass()`.
     * 2) Вызывает `::from($details)`, чтобы spatie/laravel-data выполнил типизацию вложенных
     *    структур.
     *
     * @param  array<string, mixed>  $details
     * @return TData
     */
    protected function dataFrom(array $details): AbstractDetailsData
    {
        $dataClass = $this->dataClass();

        return $dataClass::from($details);
    }

    /**
     * Этот метод проверяет, что presenter получил Data-объект своего шаблона.
     * Шаги:
     * 1) Сравнивает объект с ожидаемым class-string.
     * 2) Если тип неверный — бросает `InvalidArgumentException` с именами presenter и классов.
     * 3) Возвращает тот же объект, но с уточнённым generic-типом для статического анализа.
     *
     * @template TExpected of AbstractDetailsData
     *
     * @param  class-string<TExpected>  $expectedClass
     * @return TExpected
     */
    protected function ensureData(AbstractDetailsData $data, string $expectedClass): AbstractDetailsData
    {
        if (! $data instanceof $expectedClass) {
            throw new InvalidArgumentException(sprintf(
                '%s expects %s, got %s.',
                static::class,
                $expectedClass,
                $data::class,
            ));
        }

        return $data;
    }
}
