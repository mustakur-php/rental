<?php

namespace App\Livewire\Attachments;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Database\Eloquent\Model;

class AttachmentManager extends Component
{
    use WithFileUploads;

    public Model $attachable;
    public $file;
    public string $category = 'other';
    public ?int $previewAttachmentId = null;

    public function upload(): void
    {
        $this->validate([
            'file'     => ['required', 'file', 'max:20480'], // 20 MB — يتوافق مع upload_max_filesize على السيرفر
            'category' => ['required', 'string', 'max:100'],
        ], [
            'file.required' => 'يرجى اختيار ملف أولاً',
            'file.max'      => 'حجم الملف يجب ألا يتجاوز 20 ميجابايت',
        ]);

        $path = $this->file->store('attachments', 'public');

        $this->attachable->attachments()->create([
            'original_name' => $this->file->getClientOriginalName(),
            'stored_name'   => basename($path),
            'disk'          => 'public',
            'path'          => $path,
            'mime_type'     => $this->file->getMimeType(),
            'size_bytes'    => $this->file->getSize(),
            'file_type'     => str_starts_with((string) $this->file->getMimeType(), 'image/') ? 'image' : 'document',
            'category'      => $this->category,
        ]);

        $this->reset(['file', 'category']);
        $this->category = 'other';
        $this->attachable = $this->attachable->fresh(['attachments']);
        $this->dispatch('notify', message: 'تم رفع المرفق بنجاح');
    }

    public function preview(int $attachmentId): void
    {
        $this->previewAttachmentId = $attachmentId;
    }

    public function closePreview(): void
    {
        $this->previewAttachmentId = null;
    }

    public function delete(int $attachmentId): void
    {
        $attachment = $this->attachable->attachments()->findOrFail($attachmentId);
        $attachment->delete();
        $this->attachable = $this->attachable->fresh(['attachments']);
    }

    public function render()
    {
        return view('livewire.attachments.attachment-manager', [
            'attachments' => $this->attachable->attachments ?? collect(),
            'previewAttachment' => $this->previewAttachmentId
                ? $this->attachable->attachments()->find($this->previewAttachmentId)
                : null,
        ]);
    }
}
