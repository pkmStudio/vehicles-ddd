<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Notifications;

use App\User\Models\User;

interface FileNotificationServiceInterface
{
    public function send(User $user, string $csvPath, int $filesCount = 1): void;
}
