<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles\Import;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\Command\EngineCommandImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\Command\EngineModificationImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\Command\ManufacturerCommandImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\Command\ModificationCommandImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\Command\VehicleCommandImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\External\EngineCrossImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\External\ManufacturerImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\ImportRunContextDTO;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Engine\EngineMultiSheetImport;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Vehicle\VehicleMultiSheetImport;
use Maatwebsite\Excel\Concerns\WithEvents;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class QueuedExcelImportSerializationTest extends TestCase
{
    /**
     * @param  class-string  $binding
     */
    #[DataProvider('queuedImportBindings')]
    public function test_queued_import_adapter_and_listeners_are_serializable(string $binding): void
    {
        $this->assertSerializableImport(app($binding));
    }

    public function test_multi_sheet_import_adapters_and_sheets_are_serializable(): void
    {
        $this->assertSerializableMultiSheetImport(
            app(VehicleMultiSheetImport::class),
            new ImportRunContextDTO(userId: 42, operationId: 'vehicle-multi-sheet-serialization'),
        );
        $this->assertSerializableMultiSheetImport(
            app(EngineMultiSheetImport::class),
            new ImportRunContextDTO(userId: 42, operationId: 'engine-multi-sheet-serialization'),
        );
    }

    /**
     * @return array<string, array{class-string}>
     */
    public static function queuedImportBindings(): array
    {
        return [
            'manufacturer external import' => [ManufacturerImportInterface::class],
            'engine cross import' => [EngineCrossImportInterface::class],
            'manufacturer command import' => [ManufacturerCommandImportInterface::class],
            'engine command import' => [EngineCommandImportInterface::class],
            'modification command import' => [ModificationCommandImportInterface::class],
            'engine modification command import' => [EngineModificationImportInterface::class],
            'vehicle command import' => [VehicleCommandImportInterface::class],
        ];
    }

    private function assertSerializableImport(object $import): void
    {
        $this->assertIsString(serialize($import));

        if (! $import instanceof WithEvents) {
            return;
        }

        foreach ($import->registerEvents() as $listener) {
            $this->assertIsString(serialize($listener));
        }
    }

    private function assertSerializableMultiSheetImport(
        VehicleMultiSheetImport|EngineMultiSheetImport $import,
        ImportRunContextDTO $context,
    ): void {
        $import->context = $context;

        $this->assertSerializableImport($import);

        foreach ($import->sheets() as $sheet) {
            $this->assertIsString(serialize($sheet));
        }
    }
}
