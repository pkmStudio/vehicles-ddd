<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Events;

use App\User\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract readonly class AbstractImportCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $user,
        public string $cacheKey
    ) {}
}
