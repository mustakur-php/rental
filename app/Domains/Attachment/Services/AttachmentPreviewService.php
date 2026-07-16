<?php

namespace App\Domains\Attachment\Services;

class AttachmentPreviewService
{
    public function previewType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }

        if ($mimeType === 'application/pdf') {
            return 'pdf';
        }

        return 'download';
    }
}
