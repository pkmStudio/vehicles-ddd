<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Exports\StaticFile;

use App\Modules\Vehicles\Features\Export\Domain\Contracts\Exports\FileExportInterface;
use App\Modules\Vehicles\Features\Export\Domain\DTOs\ExportRunContextDTO;
use App\Modules\Vehicles\Features\Export\Domain\Enums\ExportTypeEnum;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Базовый adapter копирования заранее подготовленного файла Vehicles в export disk.
 */
abstract readonly class AbstractStaticVehicleFileExport implements FileExportInterface
{
    /**
     * Возвращает export type для имени output artifact.
     */
    abstract protected function exportType(): ExportTypeEnum;

    /**
     * Возвращает локальный storage path исходного файла.
     */
    abstract protected function sourcePath(): string;

    /**
     * Копирует локальный storage-файл на внешний export disk и возвращает path результата.
     */
    public function export(ExportRunContextDTO $context, ?string $disk = null): string
    {
        $sourcePath = $this->sourcePath();
        $absoluteSourcePath = storage_path($sourcePath);
        if (! is_file($absoluteSourcePath)) {
            throw new RuntimeException("Static export source file not found: {$sourcePath}");
        }

        $disk ??= (string) config('vehicles.export.output.disk');
        $directory = trim((string) config('vehicles.export.output.directory'), '/');
        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION) ?: 'csv';
        $fileName = sprintf('%s-%s.%s', $this->exportType()->filePrefix(), $context->operationId, $extension);
        $path = $directory !== '' ? sprintf('%s/%s', $directory, $fileName) : $fileName;

        $stream = fopen($absoluteSourcePath, 'rb');
        if ($stream === false) {
            throw new RuntimeException("Cannot open static export source file: {$sourcePath}");
        }

        Storage::disk($disk)->writeStream($path, $stream);

        if (is_resource($stream)) {
            fclose($stream);
        }

        return $path;
    }
}
