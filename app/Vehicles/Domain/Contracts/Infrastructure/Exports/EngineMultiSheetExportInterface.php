<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Infrastructure\Exports;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

interface EngineMultiSheetExportInterface
{
    /**
     * Сформировать файл выгрузки на скачивание. Транспорт (Excel) — в реализации.
     */
    public function download(string $fileName): BinaryFileResponse;
}
