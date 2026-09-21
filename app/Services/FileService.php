<?php

namespace App\Services;

use App\Models\File;
use App\Models\Submission;
use App\Models\SubmissionAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
                str_contains($modelName, 'user') => 'users',
                default => $modelName.'s',
            };
        }

        $folder = 'uploads/'.$entityFolder.'/'.date('Y/m');

        $filenameOnly = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $uniqueFileName = \Str::slug($filenameOnly).'_'.time().'.'.$extension;

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

    public function downloadFile(File $file): BinaryFileResponse
    {
        if (! Storage::disk('local')->exists($file->path)) {
            abort(404, __('files.file_not_found'));
        }

        $fullPath = Storage::disk('local')->path($file->path);

        return response()->download($fullPath, $file->original_name);
    }

    public function deleteFile(File $file): bool
    {
        if (Storage::disk('local')->exists($file->path)) {
            Storage::disk('local')->delete($file->path);
        }

        return $file->delete();
    }

    public function updateAvatar(UploadedFile $file, User $user): File
    {
        $oldAvatar = File::where('user_id', $user->id)
            ->where('fileable_type', get_class($user))
            ->where('fileable_id', $user->id)
            ->first();

        if ($oldAvatar) {
            $this->deleteFile($oldAvatar);
        }

        $uploadedAvatar = $this->uploadFile($file, $user, $user);

        $user->update(['avatar' => $uploadedAvatar->path]);

        return $uploadedAvatar;
    }
    public function uploadSubmissionAttachment( UploadedFile $file,User $user, Submission $submission): SubmissionAttachment {
        $folder = 'uploads/tasks/' . date('Y/m');
    
        $filenameOnly = pathinfo(
            $file->getClientOriginalName(),
            PATHINFO_FILENAME
        );
    
        $extension = $file->getClientOriginalExtension();
    
        $uniqueFileName = \Str::slug($filenameOnly)
            . '_' . time()
            . '.' . $extension;
    
        $path = $file->storeAs(
            $folder,
            $uniqueFileName,
            'local'
        );
    
        try {
            return SubmissionAttachment::create([
                'submission_id' => $submission->id,
                'uploaded_by' => $user->id,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
            ]);
        } catch (\Throwable $e) {
            // Delete the uploaded file if database insertion fails.
            Storage::disk('local')->delete($path);
    
            throw $e;
        }
    }
}
