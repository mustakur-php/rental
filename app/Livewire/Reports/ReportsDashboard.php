<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Mpdf\Mpdf;
use App\Domains\Report\DTOs\ReportFilters;
use App\Domains\Report\Exports\ArrayReportExport;
use App\Domains\Report\Services\IncomeReportService;
use App\Domains\Report\Services\OutgoingReportService;
use App\Domains\Report\Services\NetReportService;
use App\Domains\Report\Services\ArrearsReportService;
use App\Domains\Report\Services\OccupancyReportService;
use App\Domains\Report\Services\MaintenanceReportService;
use App\Domains\Property\Models\Property;
use App\Domains\Unit\Models\Unit;

class ReportsDashboard extends Component
{
    public string $activeTab = 'net';

    public string $dateFrom   = '';
    public string $dateTo     = '';
    public string $propertyId = '';
    public string $unitId     = '';

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function updatedPropertyId(): void
    {
        $this->unitId = '';
    }

    protected function buildFilters(): ReportFilters
    {
        return new ReportFilters(
            dateFrom:   $this->dateFrom   ?: null,
            dateTo:     $this->dateTo     ?: null,
            propertyId: $this->propertyId ? (int) $this->propertyId : null,
            unitId:     $this->unitId     ? (int) $this->unitId     : null,
        );
    }

    // ─── تصدير Excel ────────────────────────────────────────────────
    public function exportExcel()
    {
        [$headings, $rows, $filename] = $this->buildExportData();

        return Excel::download(
            new ArrayReportExport($rows, $headings),
            $filename . '.xlsx'
        );
    }

    // ─── تصدير PDF بـ mPDF ──────────────────────────────────────────
    public function exportPdf()
    {
        [$headings, $rows, $filename, $title] = $this->buildExportData();

        $mpdf = new Mpdf([
            'mode'         => 'utf-8',
            'format'       => 'A4-L',
            'direction'    => 'rtl',
            'default_font' => 'Cairo',
            'fontDir'      => [storage_path('fonts/')],
            'fontdata'     => [
                'arial' => ['R' => 'Cairo-Regular.ttf'],
            ],
            'margin_top'    => 15,
            'margin_bottom' => 15,
            'margin_left'   => 15,
            'margin_right'  => 15,
        ]);

        $html = view('reports.pdf.tab-report', [
            'headings' => $headings,
            'rows'     => $rows,
            'title'    => $title,
            'filters'  => [
                'date_from'   => $this->dateFrom,
                'date_to'     => $this->dateTo,
                'property_id' => $this->propertyId,
                'unit_id'     => $this->unitId,
            ],
        ])->render();

        $mpdf->WriteHTML($html);

        return response()->streamDownload(
            fn () => print($mpdf->Output('', 'S')),
            $filename . '.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    // ─── بناء بيانات التصدير ────────────────────────────────────────
    protected function buildExportData(): array
    {
        $filters = $this->buildFilters();

        return match ($this->activeTab) {
            'net'         => $this->buildNetExport($filters),
            'income'      => $this->buildIncomeExport($filters),
            'outgoing'    => $this->buildOutgoingExport($filters),
            'arrears'     => $this->buildArrearsExport($filters),
            'occupancy'   => $this->buildOccupancyExport($filters),
            'maintenance' => $this->buildMaintenanceExport($filters),
            default       => [[], [], 'report', 'تقرير'],
        };
    }

    protected function buildNetExport(ReportFilters $filters): array
    {
        $rows     = app(NetReportService::class)->byProperty($filters);
        $headings = ['العقار', 'نوع الملكية', 'المالك', 'الوارد المحصّل (ر.س)', 'الصادر المدفوع (ر.س)', 'الصافي (ر.س)'];
        $data     = array_map(fn ($r) => [
            $r['property_name'],
            $r['is_leased'] ? 'مستأجر' : 'ملك',
            $r['owner_name'],
            $r['income_paid'],
            $r['outgoing_paid'],
            $r['net'],
        ], $rows);

        return [$headings, $data, 'تقرير-الصافي-'.now()->format('Ymd'), 'تقرير الصافي'];
    }

    protected function buildIncomeExport(ReportFilters $filters): array
    {
        $data     = app(IncomeReportService::class)->byProperty($filters);
        $headings = ['العقار', 'عدد الوحدات', 'الإجمالي المحصّل (ر.س)'];
        $rows     = array_map(fn ($r) => [
            $r['property_name'],
            $r['units_count'],
            $r['paid_total'],
        ], $data);

        return [$headings, $rows, 'تقرير-الوارد-'.now()->format('Ymd'), 'تقرير الإيرادات الواردة'];
    }

    protected function buildOutgoingExport(ReportFilters $filters): array
    {
        $data     = app(OutgoingReportService::class)->byProperty($filters);
        $headings = ['العقار', 'المالك', 'المستحق (ر.س)', 'المدفوع (ر.س)', 'المتبقي (ر.س)', 'تاريخ الانتهاء'];
        $rows     = array_map(fn ($r) => [
            $r['property_name'],
            $r['owner_name'],
            $r['required'],
            $r['paid'],
            $r['remaining'],
            $r['end_date'],
        ], $data);

        return [$headings, $rows, 'تقرير-الصادر-'.now()->format('Ymd'), 'تقرير المدفوعات الصادرة للملاك'];
    }

    protected function buildArrearsExport(ReportFilters $filters): array
    {
        $aging    = app(ArrearsReportService::class)->aging($filters);
        $headings = ['الفترة', 'المبلغ المتأخر (ر.س)'];
        $rows     = [
            ['0-30 يوم',  $aging['0_30']],
            ['31-60 يوم', $aging['31_60']],
            ['61-90 يوم', $aging['61_90']],
            ['+90 يوم',   $aging['90_plus']],
            ['الإجمالي',  array_sum($aging)],
        ];

        return [$headings, $rows, 'تقرير-المتأخرات-'.now()->format('Ymd'), 'تقرير المتأخرات'];
    }

    protected function buildOccupancyExport(ReportFilters $filters): array
    {
        $occ      = app(OccupancyReportService::class)->summary($filters);
        $headings = ['البيان', 'القيمة'];
        $rows     = [
            ['إجمالي الوحدات', $occ['total_units']],
            ['مؤجرة',          $occ['rented_units']],
            ['شاغرة',          $occ['vacant_units']],
            ['نسبة الإشغال',   $occ['occupancy_rate'] . '%'],
        ];

        return [$headings, $rows, 'تقرير-الإشغال-'.now()->format('Ymd'), 'تقرير الإشغال'];
    }

    protected function buildMaintenanceExport(ReportFilters $filters): array
    {
        $m        = app(MaintenanceReportService::class)->summary($filters);
        $headings = ['البيان', 'القيمة'];
        $rows     = [
            ['إجمالي الطلبات', $m['total_requests']],
            ['طلبات مفتوحة',   $m['open_requests']],
            ['مكتملة',         $m['completed_requests']],
            ['إجمالي التكلفة', number_format($m['total_cost'], 2) . ' ر.س'],
        ];

        return [$headings, $rows, 'تقرير-الصيانة-'.now()->format('Ymd'), 'تقرير الصيانة'];
    }

    // ─── Render ──────────────────────────────────────────────────────
    public function render()
    {
        $filters    = $this->buildFilters();
        $properties = Property::notArchived()->orderBy('name')->get(['id', 'name']);
        $units      = Unit::query()
            ->notArchived()
            ->with('property:id,name')
            ->whereHas('property', fn ($query) => $query->notArchived())
            ->when($this->propertyId, fn ($query) => $query->where('property_id', $this->propertyId))
            ->orderBy('name')
            ->get(['id', 'property_id', 'name', 'code', 'internal_number']);

        // حساب بيانات التبويب النشط فقط + المشتركة
        $income        = app(IncomeReportService::class)->summary($filters);
        $outgoing      = app(OutgoingReportService::class)->summary($filters);
        $net           = app(NetReportService::class)->summary($filters);
        $netByProperty = app(NetReportService::class)->byProperty($filters);
        $arrearsAging  = app(ArrearsReportService::class)->aging($filters);
        $occupancy     = app(OccupancyReportService::class)->summary($filters);
        $maintenance   = app(MaintenanceReportService::class)->summary($filters);

        return view('livewire.reports.reports-dashboard', compact(
            'income', 'outgoing', 'net',
            'arrearsAging', 'occupancy', 'maintenance',
            'netByProperty', 'properties', 'units'
        ))->layout('layouts.app', ['title' => 'التقارير']);
    }
}
