<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Domain\Contracts\Services;

use App\Modules\Applicability\Features\Export\Domain\DTOs\ModificationKitApplicabilityRowDTO;
use Illuminate\Support\Collection;

interface ModificationKitApplicabilityExportServiceInterface
{
    /**
     * Возвращает строки применяемости комплектов к модификациям.
     *
     * @return Collection<int, ModificationKitApplicabilityRowDTO>
     */
    public function getRows(): Collection;

    /**
     * Преобразует строку применяемости к модификации в порядок колонок Excel.
     *
     * @return array{0: int, 1: int, 2: int}
     */
    public function mapRow(ModificationKitApplicabilityRowDTO $row): array;

    /**
     * Возвращает заголовки XLSX-файла, совместимые с импортом `kit_applicability`.
     *
     * @return array{0: string, 1: string, 2: string}
     */
    public function getHeadings(): array;
}
