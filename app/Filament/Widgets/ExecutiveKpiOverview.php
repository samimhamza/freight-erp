<?php

namespace App\Filament\Widgets;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\MasterBills\MasterBillResource;
use App\Models\Customer;
use App\Models\HouseBill;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\MasterBill;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ExecutiveKpiOverview extends BaseWidget
{
    protected static ?int $sort = 0;

    /**
     * Check if the authenticated user has permission to view this widget.
     * The widget is visible if the user can view invoices, master bills, or customers.
     */
    public static function canView(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->can('viewAny', Invoice::class)
            || $user->can('viewAny', MasterBill::class)
            || $user->can('viewAny', Customer::class);
    }

    /**
     * Build top-level KPI stats cards based on permissions.
     *
     * @return array<Stat>
     */
    public function getStats(): array
    {
        $user = auth()->user();
        $stats = [];

        if ($user?->can('viewAny', Invoice::class)) {
            $stats[] = $this->buildRevenueYtdStat();
            $stats[] = $this->buildOutstandingReceivablesStat();
        }

        if ($user?->can('viewAny', MasterBill::class)) {
            $canViewHbl = $user?->can('viewAny', HouseBill::class) ?? false;
            $stats[] = $this->buildActiveShipmentsStat($canViewHbl);
        }

        if ($user?->can('viewAny', Customer::class)) {
            $stats[] = $this->buildTotalCustomersStat();
        }

        return $stats;
    }

    /**
     * Total Revenue (YTD) Stat.
     */
    protected function buildRevenueYtdStat(): Stat
    {
        $currentYear = now()->year;
        $startOfYear = Carbon::create($currentYear, 1, 1)->startOfDay();
        $endOfYear = Carbon::create($currentYear, 12, 31)->endOfDay();

        $revenueYtd = (float) (InvoiceItem::query()
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->whereNull('invoices.deleted_at')
            ->whereNull('invoice_items.deleted_at')
            ->where('invoices.status', '!=', InvoiceStatus::CANCELLED->value)
            ->whereBetween('invoices.invoice_date', [$startOfYear->toDateString(), $endOfYear->toDateString()])
            ->selectRaw('SUM((invoice_items.quantity * invoice_items.amount) + invoice_items.vat) as total')
            ->value('total') ?? 0.0);

        $sparkline = $this->getRevenueSparkline();

        $stat = Stat::make('Revenue (YTD)', 'AED '.number_format($revenueYtd, 2))
            ->description("{$currentYear} billed revenue")
            ->descriptionIcon('heroicon-m-arrow-trending-up')
            ->color('success')
            ->chart($sparkline);

        if ($url = $this->getResourceUrl(InvoiceResource::class)) {
            $stat->url($url);
        }

        return $stat;
    }

    /**
     * Outstanding Receivables Stat.
     */
    protected function buildOutstandingReceivablesStat(): Stat
    {
        $openInvoices = Invoice::query()
            ->whereNull('deleted_at')
            ->whereNotIn('status', [InvoiceStatus::PAID->value, InvoiceStatus::CANCELLED->value])
            ->select(['id', 'status'])
            ->selectRaw('(SELECT COALESCE(SUM((quantity * amount) + vat), 0) FROM invoice_items WHERE invoice_items.invoice_id = invoices.id AND invoice_items.deleted_at IS NULL) as total_billed')
            ->selectRaw('(SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payments.invoice_id = invoices.id AND payments.deleted_at IS NULL) as total_paid')
            ->get();

        $outstandingTotal = 0.0;
        $overdueCount = 0;

        foreach ($openInvoices as $invoice) {
            $remaining = (float) $invoice->total_billed - (float) $invoice->total_paid;
            if ($remaining > 0) {
                $outstandingTotal += $remaining;
            }

            if ($invoice->status === InvoiceStatus::OVERDUE) {
                $overdueCount++;
            }
        }

        $description = $overdueCount > 0
            ? "{$overdueCount} overdue invoice(s)"
            : 'Pending customer collections';

        $color = $overdueCount > 0 ? 'danger' : ($outstandingTotal > 0 ? 'warning' : 'success');
        $icon = $overdueCount > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-clock';

        $sparkline = $this->getReceivablesSparkline();

        $stat = Stat::make('Outstanding Receivables', 'AED '.number_format($outstandingTotal, 2))
            ->description($description)
            ->descriptionIcon($icon)
            ->color($color)
            ->chart($sparkline);

        if ($url = $this->getResourceUrl(InvoiceResource::class)) {
            $stat->url($url);
        }

        return $stat;
    }

    /**
     * Active Shipments Stat.
     */
    protected function buildActiveShipmentsStat(bool $canViewHbl = true): Stat
    {
        $activeMblCount = MasterBill::query()
            ->whereNull('deleted_at')
            ->whereNull('ata')
            ->count();

        $description = 'In-transit ocean voyages';

        if ($canViewHbl) {
            $activeHblCount = HouseBill::query()
                ->whereNull('deleted_at')
                ->whereHas('masterBill', fn ($q) => $q->whereNull('ata'))
                ->count();

            $description = number_format($activeHblCount).' linked House Bills';
        }

        $sparkline = $this->getShipmentsSparkline();

        $stat = Stat::make('Active Shipments', number_format($activeMblCount).' MBL')
            ->description($description)
            ->descriptionIcon('heroicon-m-globe-americas')
            ->color('info')
            ->chart($sparkline);

        if ($url = $this->getResourceUrl(MasterBillResource::class)) {
            $stat->url($url);
        }

        return $stat;
    }

    /**
     * Total Customers Stat.
     */
    protected function buildTotalCustomersStat(): Stat
    {
        $totalCustomers = Customer::query()
            ->whereNull('deleted_at')
            ->count();

        $activeCustomers = Customer::query()
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->has('houseBills')->orHas('invoices');
            })
            ->count();

        $sparkline = $this->getCustomersSparkline();

        $stat = Stat::make('Total Customers', number_format($totalCustomers))
            ->description(number_format($activeCustomers).' active clients')
            ->descriptionIcon('heroicon-m-user-group')
            ->color('primary')
            ->chart($sparkline);

        if ($url = $this->getResourceUrl(CustomerResource::class)) {
            $stat->url($url);
        }

        return $stat;
    }

    /**
     * Get 6-month monthly revenue sparkline.
     *
     * @return array<float>
     */
    protected function getRevenueSparkline(): array
    {
        $sparkline = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $start = $date->copy()->startOfMonth()->toDateString();
            $end = $date->copy()->endOfMonth()->toDateString();

            $total = (float) (InvoiceItem::query()
                ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
                ->whereNull('invoices.deleted_at')
                ->whereNull('invoice_items.deleted_at')
                ->where('invoices.status', '!=', InvoiceStatus::CANCELLED->value)
                ->whereBetween('invoices.invoice_date', [$start, $end])
                ->selectRaw('SUM((invoice_items.quantity * invoice_items.amount) + invoice_items.vat) as total')
                ->value('total') ?? 0.0);

            $sparkline[] = round($total, 2);
        }

        return $sparkline;
    }

    /**
     * Get 6-month receivables sparkline.
     *
     * @return array<float>
     */
    protected function getReceivablesSparkline(): array
    {
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $key = $date->format('Y-m');
            $months[$key] = 0.0;
        }

        $earliestDate = now()->subMonths(5)->startOfMonth()->toDateString();
        $latestDate = now()->endOfMonth()->toDateString();

        $invoices = Invoice::query()
            ->whereNull('deleted_at')
            ->whereNotIn('status', [InvoiceStatus::PAID->value, InvoiceStatus::CANCELLED->value])
            ->whereBetween('invoice_date', [$earliestDate, $latestDate])
            ->select(['id', 'invoice_date'])
            ->selectRaw('(SELECT COALESCE(SUM((quantity * amount) + vat), 0) FROM invoice_items WHERE invoice_items.invoice_id = invoices.id AND invoice_items.deleted_at IS NULL) as total_billed')
            ->selectRaw('(SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payments.invoice_id = invoices.id AND payments.deleted_at IS NULL) as total_paid')
            ->get();

        foreach ($invoices as $inv) {
            $monthKey = Carbon::parse($inv->invoice_date)->format('Y-m');
            if (isset($months[$monthKey])) {
                $rem = (float) $inv->total_billed - (float) $inv->total_paid;
                if ($rem > 0) {
                    $months[$monthKey] += $rem;
                }
            }
        }

        return array_map(fn ($val) => round($val, 2), array_values($months));
    }

    /**
     * Get 6-month active shipments sparkline.
     *
     * @return array<float>
     */
    protected function getShipmentsSparkline(): array
    {
        $sparkline = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $start = $date->copy()->startOfMonth()->toDateTimeString();
            $end = $date->copy()->endOfMonth()->toDateTimeString();

            $count = MasterBill::query()
                ->whereNull('deleted_at')
                ->whereBetween('date', [$start, $end])
                ->count();

            $sparkline[] = (float) $count;
        }

        return $sparkline;
    }

    /**
     * Get 6-month customer growth sparkline.
     *
     * @return array<float>
     */
    protected function getCustomersSparkline(): array
    {
        $sparkline = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $end = $date->copy()->endOfMonth()->toDateTimeString();

            $count = Customer::query()
                ->whereNull('deleted_at')
                ->where('created_at', '<=', $end)
                ->count();

            $sparkline[] = (float) $count;
        }

        return $sparkline;
    }

    /**
     * Safely resolve resource URL without throwing if outside request context.
     *
     * @param  class-string  $resourceClass
     */
    protected function getResourceUrl(string $resourceClass): ?string
    {
        try {
            return $resourceClass::getUrl('index');
        } catch (\Throwable) {
            return null;
        }
    }
}
