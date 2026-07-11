<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Domain\Contracts\UseCases\External;

use App\Vehicles\Export\Domain\DTOs\ExportFileRequestDTO;

interface StartExportUseCaseInterface
{
    public function execute(ExportFileRequestDTO $request): void;
}
