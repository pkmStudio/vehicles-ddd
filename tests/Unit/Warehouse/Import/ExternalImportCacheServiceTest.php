<?php

declare(strict_types=1);

namespace Tests\Unit\Warehouse\Import;

use App\Modules\Warehouse\Features\Import\Domain\DTOs\ExternalImportFileRequestDTO;
use App\Modules\Warehouse\Features\Import\Domain\Enums\ImportTypeEnum;
use App\Modules\Warehouse\Features\Import\Infrastructure\Services\External\ExternalImportCacheService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Regression: `Cache::get()` on a value written via Redis (`Illuminate\Cache\RedisStore`) can come
 * back as `__PHP_Incomplete_Class` for custom objects even though the same round-trip works fine
 * for plain arrays — confirmed manually against the project's real Redis cache store (the `array`
 * driver used in tests doesn't serialize at all, so it can't catch this). `rememberCleanup()` must
 * therefore store a plain array, never a DTO instance.
 */
final class ExternalImportCacheServiceTest extends TestCase
{
    public function test_remembered_cleanup_is_stored_as_a_plain_array_not_an_object(): void
    {
        config(['warehouse.import.external.cache.keys.cleanup' => 'cleanup_test_%s']);

        $service = new ExternalImportCacheService;
        $service->rememberCleanup(new ExternalImportFileRequestDTO(
            userId: 1,
            runId: 'run-1',
            importType: ImportTypeEnum::Nomenclature,
            disk: 's3',
            path: 'warehouse/file.xlsx',
        ));

        $raw = Cache::get('cleanup_test_run-1');

        $this->assertIsArray($raw);
        $this->assertSame(['disk' => 's3', 'path' => 'warehouse/file.xlsx'], $raw);

        // Проверяет то же самое, что сломало бы Redis-кэш на объекте: сырое значение обязано
        // пережить голый PHP serialize()/unserialize() без класса-заглушки на выходе.
        $roundTripped = unserialize(serialize($raw));
        $this->assertSame($raw, $roundTripped);
    }

    public function test_pull_cleanup_reconstructs_dto_and_removes_key(): void
    {
        config(['warehouse.import.external.cache.keys.cleanup' => 'cleanup_test_%s']);

        $service = new ExternalImportCacheService;
        $service->rememberCleanup(new ExternalImportFileRequestDTO(
            userId: 1,
            runId: 'run-2',
            importType: ImportTypeEnum::Nomenclature,
            disk: 's3',
            path: 'warehouse/file.xlsx',
        ));

        $dto = $service->pullCleanup('run-2');

        $this->assertNotNull($dto);
        $this->assertSame('s3', $dto->disk);
        $this->assertSame('warehouse/file.xlsx', $dto->path);
        $this->assertFalse(Cache::has('cleanup_test_run-2'));
    }

    public function test_pull_cleanup_returns_null_when_nothing_remembered(): void
    {
        $service = new ExternalImportCacheService;

        $this->assertNull($service->pullCleanup('never-remembered'));
    }
}
