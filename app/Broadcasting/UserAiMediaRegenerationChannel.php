<?php

declare(strict_types=1);

namespace App\Broadcasting;

use App\Models\User;

class UserAiMediaRegenerationChannel
{
    public function join(User $user, User $owner, string $regenerationId): bool
    {
        return $user->is($owner);
    }
}
