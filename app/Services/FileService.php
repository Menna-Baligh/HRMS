<?php
namespace App\Services;

use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class FileService
{

    public function uploadFile(UploadedFile $file, User $user, ?Model $fileable = null): File
    {
        $folder = 'uploads/' . date('Y/m');
        $path = $file->store($folder, 'local');

        return File::create([
            'user_id'       => $user->id,
            'original_name' => $file->getClientOriginalName(),
            'path'          => $path,
            'mime_type'     => $file->getClientMimeType(),
            'size'          => $file->getSize(), 
            'fileable_type' => $fileable ? get_class($fileable) : null,
            'fileable_id'   => $fileable ? $fileable->id : null,
        ]);
    }
}
