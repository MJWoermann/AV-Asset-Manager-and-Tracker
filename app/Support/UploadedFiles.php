<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

class UploadedFiles
{
    /**
     * @return list<UploadedFile>
     */
    public static function normalize(UploadedFile|array|null $files): array
    {
        if ($files === null) {
            return [];
        }

        if ($files instanceof UploadedFile) {
            return [$files];
        }

        return array_values(array_filter(
            $files,
            fn ($file): bool => $file instanceof UploadedFile,
        ));
    }
}
