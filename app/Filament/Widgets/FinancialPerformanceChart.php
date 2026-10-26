<?php

namespace App\Filament\Widgets;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\RemittanceAdvice;
use App\Models\RemittanceAdviceItem;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Collection;

class FinancialPerformanceChart extends ChartWidget
{
    protected ?string $heading = 'Financial Performance';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '320px';

    public ?string $filter = 'monthly';

    /**
     * Check if the authenticated user has permission to view this widget.
     * The widget is visible if the user can view invoices OR payable invoices.
     */
    public static function canView(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->can('viewAny', Invoice::class) || $user->can('viewAny', RemittanceAdvice::class);
    }

    /**
     * Clear cached data when filter is changed.
     */
    public function updatedFilter(): void
    {
        $this->cachedData = null;
    }

    /**
     * Get cached chart data.
     *
     * @return array<string, mixed>
     */
    public function getCachedData(): array
    {
        return parent::getCachedData();
    }

    /**
     * Available period filters for this chart.
     *
     * @return array<string, string>
     */
    protected function getFilters(): ?array
    {
        return [
            'monthly' => 'This Year (Monthly)',
            'quarterly' => 'This Year (Quarterly)',
            'yearly' => '5-Year Trend (Yearly)',
        ];
    }

    /**
     * Dynamic description displaying aggregate totals for the authorized entities.
     */
    public function getDescription(): ?string
    {
        $user = auth()->user();
        $canViewInvoices = $user?->can('viewAny', Invoice::class) ?? false;
        $canViewPayables = $user?->can('viewAny', RemittanceAdvice::class) ?? false;

        if (! $canViewInvoices && ! $canViewPayables) {
            return null;
        }

        $aggregates = $this->calculateAggregates($canViewInvoices, $canViewPayables);

        $filterLabel = match ($this->filter) {
            'quarterly' => 'This Year (Quarterly)',
            'yearly' => 'Last 5 Years (Yearly)',
            default => 'This Year (Monthly)',
        };

        $parts = [];
        if ($canViewInvoices) {
            $parts[] = 'Invoiced: AED '.number_format($aggregates['total_invoiced'], 2);
        }
        if ($canViewPayables) {
            $parts[] = 'Payables: AED '.number_format($aggregates['total_payables'], 2);
        }
        if ($canViewInvoices && $canViewPayables) {
            $parts[] = 'Net Margin: AED '.number_format($aggregates['total_net'], 2);
        }

        return "{$filterLabel} • ".implode(' | ', $parts);
    }

    /**
     * Chart type.
     */
    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * Chart.js display options.
     *
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }

    /**
     * Load chart dataset and labels based on permissions.
     *
     * @return array{datasets: array<int, array<string, mixed>>, labels: array<int, string>}
     */
    protected function getData(): array
    {
        $user = auth()->user();
        $canViewInvoices = $user?->can('viewAny', Invoice::class) ?? false;
        $canViewPayables = $user?->can('viewAny', RemittanceAdvice::class) ?? false;

        $filter = $this->filter ?? 'monthly';

        return match ($filter) {
            'quarterly' => $this->getQuarterlyData($canViewInvoices, $canViewPayables),
            'yearly' => $this->getYearlyData($canViewInvoices, $canViewPayables),
            default => $this->getMonthlyData($canViewInvoices, $canViewPayables),
        };
    }

    /**
     * Get monthly breakdown for current year.
     *
     * @return array{datasets: array<int, array<string, mixed>>, labels: array<int, string>}
     */
    protected function getMonthlyData(bool $canViewInvoices, bool $canViewPayables): array
    {
        $currentYear = now()->year;
        $startDate = Carbon::create($currentYear, 1, 1)->startOfDay();
        $endDate = Carbon::create($currentYear, 12, 31)->endOfDay();

        $invoicedData = [];
        $payableData = [];
        $netData = [];

        if ($canViewInvoices) {
            $invoices = $this->queryInvoiceItems($startDate, $endDate);
            $invoicedMonthly = array_fill(1, 12, 0.0);

            foreach ($invoices as $item) {
                $month = (int) Carbon::parse($item->invoice_date)->format('n');
                $invoicedMonthly[$month] += $this->calculateItemTotal($item);
            }

            for ($m = 1; $m <= 12; $m++) {
                $invoicedData[] = round($invoicedMonthly[$m], 2);
            }
        }

        if ($canViewPayables) {
            $payables = $this->queryPayableInvoiceItems($startDate, $endDate);
            $payablesMonthly = array_fill(1, 12, 0.0);

            foreach ($payables as $item) {
                $month = (int) Carbon::parse($item->invoice_date)->format('n');
                $payablesMonthly[$month] += $this->calculateItemTotal($item);
            }

            for ($m = 1; $m <= 12; $m++) {
                $payableData[] = round($payablesMonthly[$m], 2);
            }
        }

        if ($canViewInvoices && $canViewPayables) {
            for ($m = 0; $m < 12; $m++) {
                $netData[] = round($invoicedData[$m] - $payableData[$m], 2);
            }
        }

        $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        return [
            'datasets' => $this->buildDatasets($invoicedData, $payableData, $netData, $canViewInvoices, $canViewPayables),
            'labels' => $labels,
        ];
    }

    /**
     * Get quarterly breakdown for current year.
     *
     * @return array{datasets: array<int, array<string, mixed>>, labels: array<int, string>}
     */
    protected function getQuarterlyData(bool $canViewInvoices, bool $canViewPayables): array
    {
        $currentYear = now()->year;
        $startDate = Carbon::create($currentYear, 1, 1)->startOfDay();
        $endDate = Carbon::create($currentYear, 12, 31)->endOfDay();

        $invoicedData = [];
        $payableData = [];
        $netData = [];

        if ($canViewInvoices) {
            $invoices = $this->queryInvoiceItems($startDate, $endDate);
            $invoicedQuarterly = array_fill(1, 4, 0.0);

            foreach ($invoices as $item) {
                $quarter = (int) ceil(((int) Carbon::parse($item->invoice_date)->format('n')) / 3);
                $invoicedQuarterly[$quarter] += $this->calculateItemTotal($item);
            }

            for ($q = 1; $q <= 4; $q++) {
                $invoicedData[] = round($invoicedQuarterly[$q], 2);
            }
        }

        if ($canViewPayables) {
            $payables = $this->queryPayableInvoiceItems($startDate, $endDate);
            $payablesQuarterly = array_fill(1, 4, 0.0);

            foreach ($payables as $item) {
                $quarter = (int) ceil(((int) Carbon::parse($item->invoice_date)->format('n')) / 3);
                $payablesQuarterly[$quarter] += $this->calculateItemTotal($item);
            }

            for ($q = 1; $q <= 4; $q++) {
                $payableData[] = round($payablesQuarterly[$q], 2);
            }
        }

        if ($canViewInvoices && $canViewPayables) {
            for ($q = 0; $q < 4; $q++) {
                $netData[] = round($invoicedData[$q] - $payableData[$q], 2);
            }
        }

        $labels = ['Q1 (Jan - Mar)', 'Q2 (Apr - Jun)', 'Q3 (Jul - Sep)', 'Q4 (Oct - Dec)'];

        return [
            'datasets' => $this->buildDatasets($invoicedData, $payableData, $netData, $canViewInvoices, $canViewPayables),
            'labels' => $labels,
        ];
    }

    /**
     * Get 5-year trend breakdown.
     *
     * @return array{datasets: array<int, array<string, mixed>>, labels: array<int, string>}
     */
    protected function getYearlyData(bool $canViewInvoices, bool $canViewPayables): array
    {
        $currentYear = now()->year;
        $startYear = $currentYear - 4;

        $startDate = Carbon::create($startYear, 1, 1)->startOfDay();
        $endDate = Carbon::create($currentYear, 12, 31)->endOfDay();

        $invoicedData = [];
        $payableData = [];
        $netData = [];
        $labels = [];

        for ($year = $startYear; $year <= $currentYear; $year++) {
            $labels[] = (string) $year;
        }

        if ($canViewInvoices) {
            $invoices = $this->queryInvoiceItems($startDate, $endDate);
            $invoicedYearly = array_fill_keys(array_keys(array_flip($labels)), 0.0);

            foreach ($invoices as $item) {
                $year = (string) Carbon::parse($item->invoice_date)->format('Y');
                if (isset($invoicedYearly[$year])) {
                    $invoicedYearly[$year] += $this->calculateItemTotal($item);
                }
            }

            foreach ($labels as $year) {
                $invoicedData[] = round($invoicedYearly[$year], 2);
            }
        }

        if ($canViewPayables) {
            $payables = $this->queryPayableInvoiceItems($startDate, $endDate);
            $payablesYearly = array_fill_keys(array_keys(array_flip($labels)), 0.0);

            foreach ($payables as $item) {
                $year = (string) Carbon::parse($item->invoice_date)->format('Y');
                if (isset($payablesYearly[$year])) {
                    $payablesYearly[$year] += $this->calculateItemTotal($item);
                }
            }

            foreach ($labels as $year) {
                $payableData[] = round($payablesYearly[$year], 2);
            }
        }

        if ($canViewInvoices && $canViewPayables) {
            $count = count($labels);
            for ($i = 0; $i < $count; $i++) {
                $netData[] = round($invoicedData[$i] - $payableData[$i], 2);
            }
        }

        return [
            'datasets' => $this->buildDatasets($invoicedData, $payableData, $netData, $canViewInvoices, $canViewPayables),
            'labels' => $labels,
        ];
    }

    /**
     * Calculate summary aggregates for authorized entities.
     *
     * @return array{total_invoiced: float, total_payables: float, total_net: float}
     */
    protected function calculateAggregates(bool $canViewInvoices, bool $canViewPayables): array
    {
        $currentYear = now()->year;
        $filter = $this->filter ?? 'monthly';

        if ($filter === 'yearly') {
            $startDate = Carbon::create($currentYear - 4, 1, 1)->startOfDay();
            $endDate = Carbon::create($currentYear, 12, 31)->endOfDay();
        } else {
            $startDate = Carbon::create($currentYear, 1, 1)->startOfDay();
            $endDate = Carbon::create($currentYear, 12, 31)->endOfDay();
        }

        $totalInvoiced = 0.0;
        $totalPayables = 0.0;

        if ($canViewInvoices) {
            $invoices = $this->queryInvoiceItems($startDate, $endDate);
            foreach ($invoices as $item) {
                $totalInvoiced += $this->calculateItemTotal($item);
            }
        }

        if ($canViewPayables) {
            $payables = $this->queryPayableInvoiceItems($startDate, $endDate);
            foreach ($payables as $item) {
                $totalPayables += $this->calculateItemTotal($item);
            }
        }

        return [
            'total_invoiced' => round($totalInvoiced, 2),
            'total_payables' => round($totalPayables, 2),
            'total_net' => round($totalInvoiced - $totalPayables, 2),
        ];
    }

    /**
     * Build Chart.js datasets array respecting permissions.
     *
     * @param  array<int, float>  $invoicedData
     * @param  array<int, float>  $payableData
     * @param  array<int, float>  $netData
     * @return array<int, array<string, mixed>>
     */
    protected function buildDatasets(
        array $invoicedData,
        array $payableData,
        array $netData,
        bool $canViewInvoices,
        bool $canViewPayables
    ): array {
        $datasets = [];

        if ($canViewInvoices) {
            $datasets[] = [
                'label' => 'Customer Invoices (Revenue)',
                'data' => $invoicedData,
                'backgroundColor' => 'rgba(16, 185, 129, 0.8)',
                'borderColor' => '#059669',
                'borderWidth' => 1,
                'borderRadius' => 4,
            ];
        }

        if ($canViewPayables) {
            $datasets[] = [
                'label' => 'Remittance Advices (Expenses)',
                'data' => $payableData,
                'backgroundColor' => 'rgba(239, 68, 68, 0.8)',
                'borderColor' => '#dc2626',
                'borderWidth' => 1,
                'borderRadius' => 4,
            ];
        }

        if ($canViewInvoices && $canViewPayables) {
            $datasets[] = [
                'type' => 'line',
                'label' => 'Net Margin',
                'data' => $netData,
                'borderColor' => '#3b82f6',
                'backgroundColor' => 'rgba(59, 130, 246, 0.15)',
                'borderWidth' => 2,
                'tension' => 0.25,
                'fill' => false,
            ];
        }

        return $datasets;
    }

    /**
     * Query invoice items within a date range.
     *
     * @return Collection<int, object>
     */
    protected function queryInvoiceItems(Carbon $startDate, Carbon $endDate)
    {
        return InvoiceItem::query()
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->whereNull('invoices.deleted_at')
            ->whereNull('invoice_items.deleted_at')
            ->where('invoices.status', '!=', InvoiceStatus::CANCELLED->value)
            ->whereBetween('invoices.invoice_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->select([
                'invoices.invoice_date',
                'invoice_items.quantity',
                'invoice_items.amount',
                'invoice_items.vat',
            ])
            ->get();
    }

    /**
     * Query payable invoice items within a date range.
     *
     * @return Collection<int, object>
     */
    protected function queryPayableInvoiceItems(Carbon $startDate, Carbon $endDate)
    {
        return RemittanceAdviceItem::query()
            ->join('remittance_advices', 'remittance_advices.id', '=', 'remittance_advice_items.remittance_advice_id')
            ->whereNull('remittance_advices.deleted_at')
            ->whereNull('remittance_advice_items.deleted_at')
            ->where('remittance_advices.status', '!=', InvoiceStatus::CANCELLED->value)
            ->whereBetween('remittance_advices.advice_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->select([
                'remittance_advices.advice_date as invoice_date',
                'remittance_advice_items.quantity',
                'remittance_advice_items.amount',
                'remittance_advice_items.vat',
            ])
            ->get();
    }

    /**
     * Calculate item gross amount (quantity * amount + vat).
     */
    protected function calculateItemTotal(object $item): float
    {
        $quantity = (float) ($item->quantity ?? 1);
        $amount = (float) ($item->amount ?? 0);
        $vat = (float) ($item->vat ?? 0);

        return ($quantity * $amount) + $vat;
    }
}
