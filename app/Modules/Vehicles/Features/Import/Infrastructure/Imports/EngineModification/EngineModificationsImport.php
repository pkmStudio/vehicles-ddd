<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\EngineModification;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\EngineModificationCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories\EngineModificationDataFactoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\External\EngineModificationsImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\ModificationRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\ImportRunContextDTO;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Modification\DuplicateModificationNaturalKeyDTO;
use App\Modules\Vehicles\Features\Import\Domain\Events\EngineModification\EngineModificationImportCompleted;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\EngineModification\Mappers\EngineModificationSheetRowMapper;
use App\Modules\Vehicles\Features\Import\Infrastructure\Traits\CachesImportFailures;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use LogicException;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\Failure;

/**
 * Excel-адаптер внешнего импорта связей модификаций и двигателей.
 */
final class EngineModificationsImport implements EngineModificationsImportInterface, ShouldQueue, SkipsEmptyRows, SkipsOnFailure, ToCollection, WithEvents, WithMultipleSheets, WithStartRow
{
    use CachesImportFailures;

    private ?ImportRunContextDTO $context = null;

    private ?EngineModificationSheetRowMapper $rowMapper = null;

    private ?EngineModificationDataFactoryInterface $factory = null;

    private ?EngineRepositoryInterface $engines = null;

    private ?ModificationRepositoryInterface $modifications = null;

    private ?EngineModificationCommandInterface $command = null;

    /**
     * Подготовить adapter к сериализации в очередь.
     *
     * @return array{context: ?ImportRunContextDTO, cacheKey: ?string, lockKey: ?string}
     */
    public function __serialize(): array
    {
        return [
            'context' => $this->context,
            'cacheKey' => $this->cacheKey ?? null,
            'lockKey' => $this->lockKey ?? null,
        ];
    }

    /**
     * Восстановить adapter после очереди.
     *
     * @param  array{context?: ImportRunContextDTO|null, cacheKey?: string|null, lockKey?: string|null}  $data
     */
    public function __unserialize(array $data): void
    {
        $context = $data['context'] ?? null;
        $this->context = $context instanceof ImportRunContextDTO ? $context : null;
        $this->rowMapper = null;
        $this->factory = null;
        $this->engines = null;
        $this->modifications = null;
        $this->command = null;

        if (is_string($data['cacheKey'] ?? null)) {
            $this->cacheKey = $data['cacheKey'];
        }

        if (is_string($data['lockKey'] ?? null)) {
            $this->lockKey = $data['lockKey'];
        }
    }

    /**
     * Запустить внешний импорт связей.
     *
     * Шаги:
     * 1) Сохранить context и cache keys отчета ошибок.
     * 2) Передать текущий adapter в Laravel Excel.
     */
    public function import(string $path, ImportRunContextDTO $context, ?string $disk = null): void
    {
        $this->context = $context;
        $this->cacheKey = sprintf((string) config('vehicles.import.failures.cache.keys.engine_modification_import_failures'), $context->operationId);
        $this->lockKey = sprintf((string) config('vehicles.import.failures.cache.keys.engine_modification_import_failures_lock'), $context->operationId);

        Excel::import($this, $path, $disk);
    }

    /**
     * Обработать лист связей как additive import OD-связей.
     *
     * Шаги:
     * 1) Выполнить DB preflight дублей `mod_id + type`.
     * 2) Провалидировать строки, engine existence и modification existence.
     * 3) Пропустить уже существующие связи без изменения provider.
     * 4) Добавить только новые OD-связи.
     */
    public function collection(Collection $collection): void
    {
        $duplicates = $this->modifications()->duplicateNaturalKeys();
        if ($duplicates->isNotEmpty()) {
            $this->onFailure(new Failure(
                row: $this->startRow(),
                attribute: 'Preflight',
                errors: ['В БД есть дубли mod_id + type: '.$this->formatDuplicates($duplicates)],
                values: [],
            ));

            return;
        }

        $seenRows = [];

        foreach ($collection as $index => $row) {
            $rowNumber = $index + $this->startRow();
            $rowValues = $row->toArray();

            try {
                $sheetRow = $this->rowMapper()->map($rowValues);
                $data = $this->factory()->make($sheetRow);
            } catch (ImportRowValidationException $e) {
                $this->onFailure(new Failure($rowNumber, 'Связь модификации и двигателя', $e->errors(), $rowValues));

                continue;
            }

            $type = $data->type->value;
            $groupKey = $data->modId.'|'.$type;
            $rowKey = $groupKey.'|'.$data->engId;

            if (isset($seenRows[$rowKey])) {
                $this->onFailure(new Failure($rowNumber, 'Связь модификации и двигателя', ['Дубль строки mod_id + eng_id + type.'], $rowValues));

                continue;
            }
            $seenRows[$rowKey] = true;

            if ($this->modifications()->findByModIdAndType($data->modId, $type) === null) {
                $this->onFailure(new Failure($rowNumber, 'Модификация', ["Модификация mod_id={$data->modId}, type={$type} не найдена."], $rowValues));

                continue;
            }

            if ($this->engines()->findByEngId($data->engId) === null) {
                $this->onFailure(new Failure($rowNumber, 'Двигатель', ["Двигатель eng_id={$data->engId} не найден."], $rowValues));

                continue;
            }

            $this->command()->attachIfMissing($data);
        }
    }

    /**
     * Зарегистрировать сериализуемый handler завершения импорта.
     */
    public function registerEvents(): array
    {
        return [
            AfterImport::class => [self::class, 'afterImport'],
        ];
    }

    /**
     * Опубликовать событие завершения импорта связей.
     */
    public static function afterImport(AfterImport $event): void
    {
        $import = $event->getConcernable();
        if (! $import instanceof self) {
            return;
        }

        $context = $import->context();

        event(new EngineModificationImportCompleted(
            userId: $context->userId,
            cacheKey: $import->cacheKey,
            operationId: $context->operationId,
        ));
    }

    /**
     * Вернуть первую строку данных.
     */
    public function startRow(): int
    {
        return 2;
    }

    /**
     * Ограничить импорт первым листом workbook-а.
     *
     * @return array<int, self>
     */
    public function sheets(): array
    {
        return [0 => $this];
    }

    /**
     * Получить mapper строки.
     */
    private function rowMapper(): EngineModificationSheetRowMapper
    {
        return $this->rowMapper ??= app(EngineModificationSheetRowMapper::class);
    }

    /**
     * Получить factory данных связи.
     */
    private function factory(): EngineModificationDataFactoryInterface
    {
        return $this->factory ??= app(EngineModificationDataFactoryInterface::class);
    }

    /**
     * Получить repository двигателей.
     */
    private function engines(): EngineRepositoryInterface
    {
        return $this->engines ??= app(EngineRepositoryInterface::class);
    }

    /**
     * Получить repository модификаций.
     */
    private function modifications(): ModificationRepositoryInterface
    {
        return $this->modifications ??= app(ModificationRepositoryInterface::class);
    }

    /**
     * Получить command синхронизации связей.
     */
    private function command(): EngineModificationCommandInterface
    {
        return $this->command ??= app(EngineModificationCommandInterface::class);
    }

    /**
     * Получить обязательный context запуска импорта.
     */
    private function context(): ImportRunContextDTO
    {
        return $this->context ?? throw new LogicException('Engine modifications import context is not initialized.');
    }

    /**
     * Сформатировать дубли natural key для отчета ошибок.
     *
     * @param  Collection<int, DuplicateModificationNaturalKeyDTO>  $duplicates
     */
    private function formatDuplicates(Collection $duplicates): string
    {
        return $duplicates
            ->map(static fn (DuplicateModificationNaturalKeyDTO $duplicate): string => "{$duplicate->modId}/{$duplicate->type} ({$duplicate->count})")
            ->implode(', ');
    }
}
