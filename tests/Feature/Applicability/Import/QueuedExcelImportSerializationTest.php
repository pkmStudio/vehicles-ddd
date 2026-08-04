<?php

declare(strict_types=1);

namespace Tests\Feature\Applicability\Import;

use App\Modules\Applicability\Features\Import\Domain\Contracts\Imports\KitApplicabilityImportInterface;
use Maatwebsite\Excel\Concerns\WithEvents;
use Tests\TestCase;

final class QueuedExcelImportSerializationTest extends TestCase
{
    public function test_queued_import_adapter_and_listeners_are_serializable(): void
    {
        $import = app(KitApplicabilityImportInterface::class);

        $this->assertIsString(serialize($import));

        if (! $import instanceof WithEvents) {
            return;
        }

        foreach ($import->registerEvents() as $listener) {
            $this->assertIsString(serialize($listener));
        }
    }
}
