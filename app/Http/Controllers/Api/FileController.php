<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\File;
use App\Services\FileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    public function __construct(protected FileService $fileService) {}

    public function download(File $file): StreamedResponse|BinaryFileResponse|JsonResponse
    {
        try {
            Gate::authorize('download', $file);

            return $this->fileService->downloadFile($file);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return ResponseHelper::error(
                null,
                __('files.unauthorized_access'),
                403
            );
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return ResponseHelper::error(
                null,
                __('files.file_not_found'),
                404
            );
        } catch (\Throwable $e) {
            \Log::error('File download error: ' . $e->getMessage());

            return ResponseHelper::error(
                null,
                __('files.download_failed'),
                500
            );
        }
    }

    public function destroy(File $file): JsonResponse
    {
        try {
            Gate::authorize('delete', $file);

            $this->fileService->deleteFile($file);

            return ResponseHelper::success(
                [],
                __('files.deleted_successfully')
            );
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return ResponseHelper::error(
                null,
                __('files.unauthorized_delete'),
                403
            );
        } catch (\Throwable $e) {
            \Log::error('File deletion error: ' . $e->getMessage());

            return ResponseHelper::error(
                null,
                __('files.delete_failed'),
                500
            );
        }
    }
}
