<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Domain\Enums;

enum ImportCompletionStatusEnum: string
{
    case Completed = 'completed';
    case Failed = 'failed';
}
