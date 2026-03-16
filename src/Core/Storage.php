<?php

declare(strict_types=1);

namespace AgencyOS\Core;

use Psr\Http\Message\UploadedFileInterface;

class Storage
{
    /**
     * Save an uploaded file to a local sub-directory.
     * Returns the public URL path.
     */
    public static function store(UploadedFileInterface $file, string $subDir = 'files'): array
    {
        $maxSize = Config::maxFileSize();
        if ($file->getSize() > $maxSize) {
            throw new \RuntimeException('File too large. Maximum allowed: ' . ($maxSize / 1024 / 1024) . ' MB', 413);
        }

        $originalName = $file->getClientFilename() ?? 'upload';
        $ext          = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $safeName     = Helpers::uuid() . ($ext ? ".$ext" : '');

        $uploadRoot = Config::uploadDir();
        $dir        = $uploadRoot . '/' . trim($subDir, '/');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $targetPath = $dir . '/' . $safeName;
        $file->moveTo($targetPath);

        return [
            'url'       => '/api/uploads/' . trim($subDir, '/') . '/' . $safeName,
            'path'      => $targetPath,
            'name'      => $originalName,
            'size'      => $file->getSize(),
            'type'      => $file->getClientMediaType() ?? 'application/octet-stream',
            'stored_as' => $safeName,
        ];
    }

    /**
     * Delete a locally stored file by its URL path.
     */
    public static function delete(string $urlPath): bool
    {
        // URL like /api/uploads/files/uuid.jpg
        $relative = preg_replace('#^/api/uploads/#', '', $urlPath);
        $full     = Config::uploadDir() . '/' . $relative;
        if (file_exists($full)) {
            return unlink($full);
        }
        return false;
    }

    /**
     * Serve a local file (used in routes).
     */
    public static function getLocalPath(string $subDir, string $filename): string
    {
        return Config::uploadDir() . '/' . trim($subDir, '/') . '/' . $filename;
    }

    /**
     * Detect MIME type of a file.
     */
    public static function getMimeType(string $path): string
    {
        if (function_exists('mime_content_type')) {
            return mime_content_type($path) ?: 'application/octet-stream';
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        return $finfo->file($path) ?: 'application/octet-stream';
    }
}
