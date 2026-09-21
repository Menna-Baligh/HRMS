<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        $roleValue = $user->role instanceof \BackedEnum ? $user->role->value : $user->role;

        if (in_array($roleValue, ['HR', 'Owner']) || $user->hasAnyRole(['Owner', 'HR'])) {
            return true;
        }

        return null;
    }

    public function view(User $currentUser, User $targetUser): bool
    {
        return $currentUser->id === $targetUser->id
            || $targetUser->manager_id === $currentUser->id;
    }

    public function updateHrFields(User $currentUser, User $targetUser): bool
    {
        $roleValue = $currentUser->role instanceof \BackedEnum ? $currentUser->role->value : $currentUser->role;

        return in_array($roleValue, ['Owner', 'HR']) || $currentUser->hasAnyRole(['Owner', 'HR']);
    }
}
