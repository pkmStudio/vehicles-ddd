<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\WiperAdapterAudit\Application\Services;

use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Modules\Warehouse\Features\WiperAdapterAudit\Domain\Contracts\Repositories\WiperAdapterAuditKitRepositoryInterface;
use App\Modules\Warehouse\Features\WiperAdapterAudit\Domain\Contracts\Services\WiperAdapterAuditServiceInterface;
use App\Modules\Warehouse\Features\WiperAdapterAudit\Domain\DTOs\WiperAdapterAuditRowDTO;
use App\Modules\Warehouse\Features\WiperAdapterAudit\Domain\ModelData\KitData;
use App\Modules\Warehouse\Features\WiperAdapterAudit\Domain\ModelData\TypeData;
use Illuminate\Support\Collection;

/**
 * Считает строки отчёта о несовпадении вложенных и заявленных адаптеров дворников.
 */
final readonly class WiperAdapterAuditService implements WiperAdapterAuditServiceInterface
{
    private const array TEMPLATE_BY_CHAR = [
        'WB' => NomenclatureDetailTemplateEnum::WIPER,
        'AW' => NomenclatureDetailTemplateEnum::WIPER_ADAPTER,
    ];

    private const array TEMPLATE_BY_ID = [
        3 => NomenclatureDetailTemplateEnum::WIPER,
        7 => NomenclatureDetailTemplateEnum::WIPER_ADAPTER,
    ];

    private const array TEMPLATE_BY_NAME = [
        'ЩЕТКИ СТЕКЛООЧИСТИТЕЛЯ' => NomenclatureDetailTemplateEnum::WIPER,
        'ЩЕТКА СТЕКЛООЧИСТИТЕЛЯ' => NomenclatureDetailTemplateEnum::WIPER,
        'АДАПТЕР СТЕКЛООЧИСТИТЕЛЯ' => NomenclatureDetailTemplateEnum::WIPER_ADAPTER,
        'АДАПТЕР ЩЕТКИ СТЕКЛООЧИСТИТЕЛЯ' => NomenclatureDetailTemplateEnum::WIPER_ADAPTER,
    ];

    /**
     * Получает repository наборов для аудита.
     * Шаги:
     * 1) Сохранить repository, который отдаёт комплекты с полным составом.
     * 2) Оставить расчёт mismatches внутри service, не в SQL adapter-е.
     */
    public function __construct(
        private WiperAdapterAuditKitRepositoryInterface $kits,
    ) {}

    /**
     * Возвращает строки отчёта по всем наборам с обнаруженными расхождениями.
     *
     * Шаги:
     * 1) Прочитать наборы с составом из repository.
     * 2) Для каждого набора посчитать адаптеры-товары и адаптеры в details щёток.
     * 3) Сравнить количества и вернуть только строки с расхождениями.
     *
     * @return Collection<int, WiperAdapterAuditRowDTO>
     */
    public function rows(): Collection
    {
        $rows = [];

        foreach ($this->kits->withAtLeastThreeNomenclatures() as $kit) {
            $kitAdapters = $this->extractKitAdapters($kit);
            $nomenclatureAdapters = $kitAdapters === [] ? [] : $this->extractNomenclatureAdapters($kit);

            if ($nomenclatureAdapters === []) {
                continue;
            }

            $result = $this->findAdapterQuantityMismatches(
                nomenclatureAdapters: $nomenclatureAdapters,
                kitAdapters: $kitAdapters,
                kit: $kit,
            );

            if ($result['mismatchedAdapters'] === []) {
                continue;
            }

            $rows[] = new WiperAdapterAuditRowDTO(
                kitId: (int) $kit->id,
                kit: $this->kitPartNumbers($kit),
                mismatchedAdapters: implode(';', $result['mismatchedAdapters']),
                place: implode(", \n", $result['messages']),
            );
        }

        return collect($rows);
    }

    /**
     * Извлекает адаптеры, лежащие в наборе отдельной номенклатурой-адаптером.
     * Шаги:
     * 1) Пройти состав набора в порядке repository snapshot.
     * 2) Определить template каждой позиции.
     * 3) Оставить только позиции WIPER_ADAPTER.
     * 4) Извлечь adapter_type_front из details адаптерной номенклатуры.
     * 5) Вернуть count map adapter name => количество.
     *
     * @return array<string, int>
     */
    private function extractKitAdapters(KitData $kit): array
    {
        $adapters = [];

        foreach ($kit->nomenclatures ?? [] as $nomenclature) {
            $template = $this->template($nomenclature->type);
            $isWiperAdapter = $template === NomenclatureDetailTemplateEnum::WIPER_ADAPTER;

            if (! $isWiperAdapter) {
                continue;
            }

            $adapters = array_merge(
                $adapters,
                $this->adapterList($nomenclature->details['adapter_type_front'] ?? []),
            );
        }

        return array_count_values($adapters);
    }

    /**
     * Извлекает адаптеры, заявленные в details щёток из состава набора.
     * Шаги:
     * 1) Пройти состав набора.
     * 2) Оставить только позиции с template WIPER.
     * 3) Извлечь adapter_type_front из details щётки.
     * 4) Вернуть пустой список, если ни одной заявленной адаптерной позиции нет.
     * 5) Иначе вернуть count map adapter name => количество.
     *
     * @return array<string, int>
     */
    private function extractNomenclatureAdapters(KitData $kit): array
    {
        $adapters = [];

        foreach ($kit->nomenclatures ?? [] as $nomenclature) {
            $template = $this->template($nomenclature->type);
            $isWiper = $template === NomenclatureDetailTemplateEnum::WIPER;

            if (! $isWiper) {
                continue;
            }

            $adapters = array_merge(
                $adapters,
                $this->adapterList($nomenclature->details['adapter_type_front'] ?? []),
            );
        }

        return $adapters === [] ? [] : array_count_values($adapters);
    }

    /**
     * Сравнивает количества адаптеров и возвращает старые тексты замечаний dan-center.
     * Шаги:
     * 1) Для каждого адаптера, лежащего в комплекте отдельной позицией, взять заявленное количество.
     * 2) Рассчитать difference относительно quantityInPackage комплекта.
     * 3) Для отрицательной разницы добавить сообщение о лишнем адаптере.
     * 4) Для положительной разницы добавить сообщение о недостающем адаптере.
     * 5) Вернуть список adapter names с расхождениями и человекочитаемые сообщения.
     *
     * @param  array<string, int>  $nomenclatureAdapters
     * @param  array<string, int>  $kitAdapters
     * @return array{mismatchedAdapters: list<string>, messages: list<string>}
     */
    private function findAdapterQuantityMismatches(array $nomenclatureAdapters, array $kitAdapters, KitData $kit): array
    {
        $messages = [];
        $mismatchedAdapters = [];

        foreach ($kitAdapters as $adapter => $count) {
            $nomenclatureCount = $nomenclatureAdapters[$adapter] ?? 0;
            $difference = $kit->quantityInPackage - ($nomenclatureCount + $count);
            $absoluteDifference = abs($difference);

            if ($difference < 0) {
                $mismatchedAdapters[] = $adapter;
                $messages[] = "Тут {$absoluteDifference} лишний адаптер: {$adapter}";
            } elseif ($difference > 0) {
                $mismatchedAdapters[] = $adapter;
                $messages[] = "Тут не хватает {$absoluteDifference} адаптера: {$adapter}";
            }
        }

        return [
            'mismatchedAdapters' => $mismatchedAdapters,
            'messages' => $messages,
        ];
    }

    /**
     * Определяет detail-шаблон только для двух типов, нужных аудиту адаптеров.
     * Шаги:
     * 1) Для отсутствующего type вернуть null.
     * 2) Сначала попробовать сопоставить type char.
     * 3) Затем попробовать стабильный legacy type id.
     * 4) Последним fallback-ом сопоставить normalized type name.
     */
    private function template(?TypeData $type): ?NomenclatureDetailTemplateEnum
    {
        if ($type === null) {
            return null;
        }

        $char = $type->char === null ? null : mb_strtoupper(trim($type->char));
        if ($char !== null && isset(self::TEMPLATE_BY_CHAR[$char])) {
            return self::TEMPLATE_BY_CHAR[$char];
        }

        if ($type->id !== null && isset(self::TEMPLATE_BY_ID[$type->id])) {
            return self::TEMPLATE_BY_ID[$type->id];
        }

        $name = mb_strtoupper(trim($type->name));

        return self::TEMPLATE_BY_NAME[$name] ?? null;
    }

    /**
     * Нормализует значение details adapter_type_front к списку строковых enum names.
     * Шаги:
     * 1) Для non-array значения использовать пустой список.
     * 2) Привести каждый adapter к trimmed string.
     * 3) Отфильтровать пустые значения.
     * 4) Вернуть плотный list<string>.
     *
     * @return list<string>
     */
    private function adapterList(mixed $value): array
    {
        $value = is_array($value) ? $value : [];
        $trimAdapter = fn (mixed $adapter): string => trim((string) $adapter);
        $isFilledAdapter = fn (string $adapter): bool => $adapter !== '';

        $value = array_map(
            $trimAdapter,
            $value,
        );

        return array_values(array_filter(
            array: $value,
            callback: $isFilledAdapter,
        ));
    }

    /**
     * Возвращает состав набора строкой артикулов в порядке pivot-sort.
     * Шаги:
     * 1) Прочитать partNumber из загруженного состава набора.
     * 2) Склеить артикулы через ';' для совместимости со старым отчетом.
     * 3) Вернуть пустую строку, если состав не загружен.
     */
    private function kitPartNumbers(KitData $kit): string
    {
        return $kit->nomenclatures?->pluck('partNumber')->implode(';') ?? '';
    }
}
