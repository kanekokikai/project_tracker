<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectAttachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProjectAttachmentController extends Controller
{
    private const MAX_FILE_SIZE = 10 * 1024 * 1024;

    public function index(Project $project): JsonResponse
    {
        $attachments = $project->attachments()
            ->orderByDesc('uploaded_at')
            ->get()
            ->map(fn (ProjectAttachment $attachment) => $this->formatAttachment($attachment));

        return response()->json([
            'status' => 'success',
            'data' => $attachments,
        ]);
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240'],
        ], [
            'file.required' => 'ファイルを選択してください',
            'file.max' => 'ファイルサイズは10MB以下である必要があります',
        ]);

        $file = $request->file('file');

        if (! $file || ! $file->isValid()) {
            return response()->json([
                'status' => 'error',
                'message' => 'ファイルのアップロードに失敗しました',
            ], 422);
        }

        $uploadDir = $this->uploadDirectory($project->id);

        if (! File::isDirectory($uploadDir)) {
            File::makeDirectory($uploadDir, 0755, true);
        }

        $extension = $file->getClientOriginalExtension();
        $storedFileName = uniqid().'_'.time().($extension ? '.'.$extension : '');
        $storedPath = $uploadDir.'/'.$storedFileName;
        $fileSize = $file->getSize();
        $mimeType = $file->getClientMimeType() ?: 'application/octet-stream';
        $originalName = $file->getClientOriginalName();

        if (! $file->move($uploadDir, $storedFileName)) {
            return response()->json([
                'status' => 'error',
                'message' => 'ファイルの保存に失敗しました',
            ], 500);
        }

        try {
            $attachment = DB::transaction(function () use ($project, $storedFileName, $originalName, $fileSize, $mimeType) {
                return ProjectAttachment::query()->create([
                    'project_id' => $project->id,
                    'file_name' => $storedFileName,
                    'original_file_name' => $originalName,
                    'file_size' => $fileSize,
                    'file_type' => $mimeType,
                    'uploaded_by' => 1,
                ]);
            });
        } catch (\Throwable $exception) {
            File::delete($storedPath);

            return response()->json([
                'status' => 'error',
                'message' => 'データベースエラー: '.$exception->getMessage(),
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'ファイルがアップロードされました',
            'data' => $this->formatAttachment($attachment),
        ]);
    }

    public function download(Request $request, ProjectAttachment $attachment): BinaryFileResponse|JsonResponse
    {
        $filePath = $this->filePath($attachment);

        if (! File::exists($filePath)) {
            return response()->json([
                'status' => 'error',
                'message' => 'ファイルが見つかりません',
            ], 404);
        }

        $disposition = $request->boolean('view') ? 'inline' : 'attachment';

        return response()->file($filePath, [
            'Content-Type' => $attachment->file_type ?: 'application/octet-stream',
            'Content-Disposition' => $disposition.'; filename="'.addslashes($attachment->original_file_name).'"',
        ]);
    }

    public function destroy(ProjectAttachment $attachment): JsonResponse
    {
        $filePath = $this->filePath($attachment);

        DB::transaction(function () use ($attachment, $filePath) {
            if (File::exists($filePath)) {
                File::delete($filePath);
            }

            $attachment->delete();
        });

        return response()->json([
            'status' => 'success',
            'message' => '添付ファイルが削除されました',
        ]);
    }

    private function uploadDirectory(int $projectId): string
    {
        return public_path("uploads/project_files/{$projectId}");
    }

    private function filePath(ProjectAttachment $attachment): string
    {
        return $this->uploadDirectory($attachment->project_id).'/'.$attachment->file_name;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatAttachment(ProjectAttachment $attachment): array
    {
        return [
            'id' => $attachment->id,
            'project_id' => $attachment->project_id,
            'file_name' => $attachment->file_name,
            'original_file_name' => $attachment->original_file_name,
            'file_size' => $attachment->file_size,
            'file_size_formatted' => $this->formatFileSize($attachment->file_size),
            'file_type' => $attachment->file_type,
            'uploaded_by' => $attachment->uploaded_by,
            'uploaded_at' => $attachment->uploaded_at,
            'upload_date' => $attachment->uploaded_at,
            'uploader_name' => 'システム',
        ];
    }

    private function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        }

        return $bytes.' bytes';
    }
}
