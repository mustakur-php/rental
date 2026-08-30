<?php

namespace App\Livewire\Properties;

use App\Domains\Property\Models\PropertyLease;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class LeaseContractUpload extends Component
{
    use WithFileUploads;

    public ?int   $leaseId     = null;
    public ?string $currentPath = null;
    public $file = null;

    public function mount(?int $leaseId = null, ?string $currentPath = null): void
    {
        $this->leaseId     = $leaseId;
        $this->currentPath = $currentPath;
    }

    /**
     * يُستدعى من الكومبوننت الأب بعد إنشاء العقد (وضع الإنشاء).
     */
    #[On('lease-created')]
    public function onLeaseCreated(int $leaseId): void
    {
        $this->leaseId = $leaseId;

        if ($this->file) {
            $this->save();
        }
    }

    public function save(): void
    {
        $this->validate([
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ], [
            'file.required' => 'يرجى اختيار ملف أولاً',
            'file.mimes'    => 'يجب أن يكون الملف PDF أو صورة',
            'file.max'      => 'حجم الملف لا يتجاوز 10 ميجابايت',
        ]);

        if (! $this->leaseId) {
            return; // ينتظر حتى يُنشأ العقد
        }

        $lease = PropertyLease::findOrFail($this->leaseId);

        if ($lease->contract_file_path) {
            Storage::disk('public')->delete($lease->contract_file_path);
        }

        $path = $this->file->store('owner-contracts', 'public');
        $lease->update(['contract_file_path' => $path]);

        $this->currentPath = $path;
        $this->file        = null;

        $this->dispatch('notify', message: 'تم رفع نسخة العقد بنجاح');
    }

    public function deleteFile(): void
    {
        if (! $this->leaseId || ! $this->currentPath) {
            return;
        }

        Storage::disk('public')->delete($this->currentPath);
        PropertyLease::find($this->leaseId)?->update(['contract_file_path' => null]);

        $this->currentPath = null;
        $this->dispatch('notify', message: 'تم حذف الملف');
    }

    public function render()
    {
        return view('livewire.properties.lease-contract-upload');
    }
}
