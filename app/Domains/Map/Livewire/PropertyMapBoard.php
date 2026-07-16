<?php

namespace App\Domains\Map\Livewire;

use App\Domains\Map\Models\PropertyMap;
use App\Domains\Map\Models\UnitMapMarker;
use App\Domains\Map\Services\MapCoordinateService;
use App\Domains\Property\Models\Property;
use App\Traits\HasPermissionGuard;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class PropertyMapBoard extends Component
{
    use WithFileUploads, HasPermissionGuard;

    public Property $property;
    public ?int     $activeMapId = null;
    public bool     $editMode    = false;

    // ─── Upload / Replace modal ──────────────────────
    public bool    $showUploadModal = false;
    public ?int    $replacingMapId  = null;   // null = جديدة | int = استبدال صورة موجودة
    public string  $mapName         = '';
    public string  $mapType         = 'floor_plan';
    public         $mapImage        = null;

    // ─── Mounting ────────────────────────────────────
    public function mount(Property $property): void
    {
        $this->property = $property;
        $this->mapName  = $property->name;

        // اختر الخريطة التي تحتوي على أقل عدد من الـ Pins (أكثر احتياجاً للعمل)
        // وعند التساوي يُفضَّل الأحدث (id desc)
        $bestMap = $property->maps()
            ->active()
            ->withCount('markers')
            ->orderBy('markers_count', 'asc')
            ->orderBy('id', 'desc')
            ->first();

        $this->activeMapId = $bestMap?->id;
    }

    // ─── Active map helper ───────────────────────────
    public function getActiveMapProperty(): ?PropertyMap
    {
        return $this->activeMapId
            ? PropertyMap::with('markers.unit')->find($this->activeMapId)
            : null;
    }

    // ─── Map selection ───────────────────────────────
    public function selectMap(int $mapId): void
    {
        $this->activeMapId = $mapId;
        $this->editMode    = false;
    }

    public function toggleEditMode(): void
    {
        if (! auth()->user()?->can('properties.edit')) {
            $this->dispatch('notify', message: 'ليس لديك صلاحية لتعديل الخريطة');
            return;
        }
        $this->editMode = ! $this->editMode;
    }

    // ─── Upload modal: خريطة جديدة ──────────────────
    public function openUploadModal(): void
    {
        $this->resetValidation();
        $this->replacingMapId  = null;
        $this->mapImage        = null;
        $this->mapType         = 'floor_plan';
        $this->mapName         = $this->property->name;
        $this->showUploadModal = true;
    }

    // ─── Replace modal: استبدال صورة خريطة موجودة ──
    public function openReplaceModal(int $mapId): void
    {
        $map = $this->property->maps()->findOrFail($mapId);

        $this->resetValidation();
        $this->replacingMapId  = $mapId;
        $this->mapImage        = null;
        $this->mapName         = $map->name;
        $this->mapType         = $map->map_type ?? 'floor_plan';
        $this->showUploadModal = true;
    }

    public function uploadMap(): void
    {
        if (! $this->requirePermission('properties.edit')) return;
        $this->validate([
            'mapName'  => ['required', 'string', 'max:100'],
            'mapType'  => ['required', 'in:floor_plan,satellite'],
            'mapImage' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:20480'],
        ], [
            'mapName.required'  => 'اسم الخريطة مطلوب',
            'mapImage.required' => 'يرجى اختيار صورة أو ملف',
            'mapImage.mimes'    => 'يجب أن يكون الملف JPG أو PNG أو PDF',
            'mapImage.max'      => 'الحجم الأقصى 20MB',
        ]);

        $newPath = $this->mapImage->store('property-maps', 'public');

        // ── استبدال صورة خريطة موجودة (مع الإبقاء على الـ Pins) ──
        if ($this->replacingMapId) {
            $map = $this->property->maps()->findOrFail($this->replacingMapId);

            // حذف الصورة القديمة من التخزين
            Storage::disk('public')->delete($map->image_path);

            $map->update([
                'name'       => $this->mapName,
                'image_path' => $newPath,
                'map_type'   => $this->mapType,
            ]);

            $this->activeMapId     = $map->id;
            $this->showUploadModal = false;
            $this->replacingMapId  = null;

            $this->dispatch('notify', message: 'تم تحديث الصورة — مواقع الوحدات محفوظة ✓');
            return;
        }

        // ── إنشاء خريطة جديدة ──
        $map = $this->property->maps()->create([
            'name'       => $this->mapName,
            'image_path' => $newPath,
            'map_type'   => $this->mapType,
            'sort_order' => ($this->property->maps()->max('sort_order') ?? 0) + 1,
            'status'     => 'active',
        ]);

        $this->activeMapId     = $map->id;
        $this->showUploadModal = false;
        $this->editMode        = true;

        $this->dispatch('notify', message: 'تم رفع الخريطة — ضع الوحدات عليها الآن');
    }

    public function deleteMap(int $mapId): void
    {
        if (! $this->requirePermission('properties.edit')) return;
        $map = $this->property->maps()->findOrFail($mapId);

        Storage::disk('public')->delete($map->image_path);
        $map->delete();

        $firstMap          = $this->property->maps()->active()->orderBy('sort_order')->first();
        $this->activeMapId = $firstMap?->id;
        $this->editMode    = false;

        $this->dispatch('notify', message: 'تم حذف الخريطة');
    }

    // ─── Markers ─────────────────────────────────────
    public function updateMarkerPosition(int $markerId, float $x, float $y, MapCoordinateService $service): void
    {
        abort_unless($this->editMode && auth()->user()?->can('properties.edit'), 403);

        UnitMapMarker::findOrFail($markerId)->update($service->normalize($x, $y));
    }

    public function addMarker(int $unitId, float $x, float $y, MapCoordinateService $service): void
    {
        abort_unless($this->editMode && $this->activeMapId && auth()->user()?->can('properties.edit'), 403);

        $activeMap = PropertyMap::find($this->activeMapId);
        if (! $activeMap) {
            return;
        }

        // Prevent duplicates in the same map
        if ($activeMap->markers()->where('unit_id', $unitId)->exists()) {
            return;
        }

        $coords = $service->normalize($x, $y);

        $activeMap->markers()->create([
            'unit_id'      => $unitId,
            'x_coordinate' => $coords['x_coordinate'],
            'y_coordinate' => $coords['y_coordinate'],
            'label'        => null,
        ]);
    }

    public function removeMarker(int $markerId): void
    {
        abort_unless($this->editMode && auth()->user()?->can('properties.edit'), 403);

        UnitMapMarker::findOrFail($markerId)->delete();
    }

    // ─── Render ──────────────────────────────────────
    public function render()
    {
        $maps      = $this->property->maps()->active()->orderBy('sort_order')->get();
        $activeMap = $this->activeMapId
            ? PropertyMap::with('markers.unit')->find($this->activeMapId)
            : null;

        $placedUnitIds = $activeMap
            ? $activeMap->markers->pluck('unit_id')->toArray()
            : [];

        $unplacedUnits = $this->property->units()
            ->notArchived()
            ->whereNotIn('id', $placedUnitIds)
            ->orderBy('name')
            ->get();

        return view('livewire.map.property-map-board', compact('maps', 'activeMap', 'unplacedUnits'));
    }
}
