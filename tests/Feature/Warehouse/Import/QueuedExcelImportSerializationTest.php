<?php

declare(strict_types=1);

namespace Tests\Feature\Warehouse\Import;

use App\Modules\Warehouse\Features\Import\Domain\Contracts\Imports\KitImportInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Imports\PackDimensionImportInterface;
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
        $import = app($binding);

        $this->assertIsString(serialize($import));

        if (! $import instanceof WithEvents) {
            return;
        }

        foreach ($import->registerEvents() as $listener) {
            $this->assertIsString(serialize($listener));
        }
    }

    /**
     * @return array<string, array{class-string}>
     */
    public static function queuedImportBindings(): array
    {
        return [
            'kit import' => [KitImportInterface::class],
            'pack dimension import' => [PackDimensionImportInterface::class],
        ];
    }
}
