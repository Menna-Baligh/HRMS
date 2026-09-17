<?php
namespace App\Policies;

use App\Models\File;
use App\Models\User;

class FilePolicy
{

    public function download(User $user, File $file): bool
    {
        return $user->id === $file->user_id || $user->hasAnyRole(['Owner', 'HR', 'Manager']);
    }


    public function delete(User $user, File $file): bool
    {
        return $user->id === $file->user_id || $user->hasAnyRole(['Owner', 'HR', 'Manager']);
    }
}
