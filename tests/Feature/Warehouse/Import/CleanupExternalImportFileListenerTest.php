<?php

declare(strict_types=1);

namespace Tests\Feature\Warehouse\Import;

use App\Modules\Warehouse\Features\Import\Application\Listeners\CleanupExternalImportFileListener;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Services\External\ExternalImportCacheServiceInterface;
use App\Modules\Warehouse\Features\Import\Domain\DTOs\ExternalImportFileRequestDTO;
use App\Modules\Warehouse\Features\Import\Domain\Enums\ImportTypeEnum;
use App\Modules\Warehouse\Features\Import\Domain\Events\NomenclatureImportCompleted;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class CleanupExternalImportFileListenerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    public function test_deletes_file_when_cleanup_was_remembered(): void
    {
        Storage::fake('s3');
        Storage::disk('s3')->put('warehouse/file.xlsx', 'xlsx');

        app(ExternalImportCacheServiceInterface::class)->rememberCleanup(new ExternalImportFileRequestDTO(
            userId: 1,
            runId: 'run-cleanup',
            importType: ImportTypeEnum::Nomenclature,
            disk: 's3',
            path: 'warehouse/file.xlsx',
        ));

        app(CleanupExternalImportFileListener::class)->handle(
            new NomenclatureImportCompleted(userId: 1, cacheKey: 'irrelevant', runId: 'run-cleanup'),
        );

        Storage::disk('s3')->assertMissing('warehouse/file.xlsx');
    }

    public function test_no_op_when_run_id_is_null(): void
    {
        app(CleanupExternalImportFileListener::class)->handle(
            new NomenclatureImportCompleted(userId: null, cacheKey: 'irrelevant', runId: null),
        );

        $this->addToAssertionCount(1);
    }

    public function test_no_op_when_nothing_was_remembered(): void
    {
        app(CleanupExternalImportFileListener::class)->handle(
            new NomenclatureImportCompleted(userId: null, cacheKey: 'irrelevant', runId: 'run-not-remembered'),
        );

        $this->addToAssertionCount(1);
    }
}
