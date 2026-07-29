<?php

namespace App\Modules\Documents\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentStorageService
{
    public function store(UploadedFile $file, string $organizationId): array
    {
        $disk = config('filesystems.default', 'local');
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $filename = Str::uuid()->toString().'.'.$extension;
        $directory = "documents/{$organizationId}/".now()->format('Y/m');

        $path = $file->storeAs($directory, $filename, $disk);

        return [
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType() ?: $file->getMimeType(),
            'extension' => $extension,
            'size' => $file->getSize() ?: 0,
            'checksum' => hash_file('sha256', $file->getRealPath()),
        ];
    }

    public function delete(?string $disk, ?string $path): void
    {
        if (! $disk || ! $path) {
            return;
        }

        Storage::disk($disk)->delete($path);
    }

    public function temporaryUrl(string $disk, string $path, int $minutes = 15): ?string
    {
        $filesystem = Storage::disk($disk);

        if (method_exists($filesystem, 'temporaryUrl')) {
            try {
                return $filesystem->temporaryUrl($path, now()->addMinutes($minutes));
            } catch (\RuntimeException) {
                // Local driver does not support temporary URLs.
            }
        }

        return null;
    }

    public function stream(string $disk, string $path)
    {
        return Storage::disk($disk)->readStream($path);
    }

    public function exists(string $disk, string $path): bool
    {
        return Storage::disk($disk)->exists($path);
    }
}
