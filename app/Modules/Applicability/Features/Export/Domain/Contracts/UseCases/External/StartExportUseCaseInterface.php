<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Domain\Contracts\UseCases\External;

use App\Modules\Applicability\Features\Export\Domain\DTOs\ExportFileRequestDTO;

interface StartExportUseCaseInterface
{
    public function execute(ExportFileRequestDTO $request): void;
}
