<?php

declare(strict_types=1);

namespace App\Warehouse\WiperAdapterAudit\Application\Services;

use App\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Warehouse\WiperAdapterAudit\Domain\Contracts\Repositories\WiperAdapterAuditKitRepositoryInterface;
use App\Warehouse\WiperAdapterAudit\Domain\Contracts\Services\WiperAdapterAuditServiceInterface;
use App\Warehouse\WiperAdapterAudit\Domain\DTOs\WiperAdapterAuditRowDTO;
use App\Warehouse\WiperAdapterAudit\Domain\ModelData\KitData;
use App\Warehouse\WiperAdapterAudit\Domain\ModelData\TypeData;
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
     *
     * @return array<string, int>
     */
    private function extractKitAdapters(KitData $kit): array
    {
        $adapters = [];

        foreach ($kit->nomenclatures ?? [] as $nomenclature) {
            if ($this->template($nomenclature->type) !== NomenclatureDetailTemplateEnum::WIPER_ADAPTER) {
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
     *
     * @return array<string, int>
     */
    private function extractNomenclatureAdapters(KitData $kit): array
    {
        $adapters = [];

        foreach ($kit->nomenclatures ?? [] as $nomenclature) {
            if ($this->template($nomenclature->type) !== NomenclatureDetailTemplateEnum::WIPER) {
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
     *
     * @return list<string>
     */
    private function adapterList(mixed $value): array
    {
        $value = is_array($value) ? $value : [];
        $value = array_map(
            fn (mixed $adapter): string => trim((string) $adapter),
            $value,
        );

        return array_values(array_filter(
            array: $value,
            callback: fn (string $adapter): bool => $adapter !== '',
        ));
    }

    /**
     * Возвращает состав набора строкой артикулов в порядке pivot-sort.
     */
    private function kitPartNumbers(KitData $kit): string
    {
        return $kit->nomenclatures?->pluck('partNumber')->implode(';') ?? '';
    }
}
