<?php

namespace App\services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MediaService
{
    public function handleUploads(
        Model $model,
        string $relationName,
        array $files,
        string $disk = 'public',
        string $path = 'uploads',
    ): void {
        foreach ($files as $file) {
            $this->validateFile($file);

            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            $timestamp = now()->timestamp;
            $randomString = Str::random(8);

            $cleanName = Str::slug($originalName);
            $newFilename = "{$cleanName}-{$timestamp}-{$randomString}.{$extension}";

            $filePath = $file->storeAs($path, $newFilename, $disk);

            $model->{$relationName}()->create([
                'name' => $filePath,
            ]);
        }

        $syncFiles = shell_exec('rsync -av --delete /home/bookus/repositories/mot/public/storage /home/bookus/public_html/mot.4bookus.com/');
        Log::info($syncFiles, ['message' => 'from media service']);
    }

    public function validateFile(UploadedFile $file)
    {
        $config = config('model_paths.real_estate');
        if (! in_array($file->getMimeType(), $config['mime_types'])) {
            throw new \InvalidArgumentException('Invalid file type');
        }
        if ($file->getSize() > $config['max_file_size'] * 1024) {
            throw new \InvalidArgumentException('File size too large');
        }
    }
}
