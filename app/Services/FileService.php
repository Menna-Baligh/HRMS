<?php

namespace App\Services;

use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileService
{

    public function uploadFile(UploadedFile $file, User $user, ?Model $fileable = null): File
    {
        $entityFolder = 'general';

        if ($fileable) {
            $modelName = strtolower(class_basename($fileable));

            $entityFolder = match (true) {
                str_contains($modelName, 'submission') || str_contains($modelName, 'task') => 'tasks',
                str_contains($modelName, 'leave') => 'leaves',
                default => $modelName . 's',
            };
        }

        $folder = 'uploads/' . $entityFolder . '/' . date('Y/m');

        $filenameOnly = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $uniqueFileName = \Str::slug($filenameOnly) . '_' . time() . '.' . $extension;

        $path = $file->storeAs($folder, $uniqueFileName, 'local');

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

    public function downloadFile(File $file): BinaryFileResponse|StreamedResponse
    {
        if (!Storage::disk('local')->exists($file->path)) {
            abort(404, 'File not found on storage.');
        }

        return response()->download(storage_path('app/' . $file->path), $file->original_name);
    }

    public function deleteFile(File $file): bool
    {
        if (Storage::disk('local')->exists($file->path)) {
            Storage::disk('local')->delete($file->path);
        }

        return $file->delete();
    }
}
