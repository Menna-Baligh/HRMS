<?php
namespace App\Services;

use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
    public function downloadFile(File $file): StreamedResponse
    {
        if (!Storage::disk('local')->exists($file->path)) {
            abort(404, 'File not found on storage.');
        }

        return Storage::download($file->path, $file->original_name);
    }

    public function deleteFile(File $file): bool
    {
        if (Storage::disk('local')->exists($file->path)) {
            Storage::disk('local')->delete($file->path);
        }

        return $file->delete();
    }
}
