<?php

namespace App\Filament\Widgets;

use App\Models\HouseBill;
use App\Models\MasterBill;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Collection;

class ShipmentVolumeChart extends ChartWidget
{
    protected ?string $heading = 'Shipment & Operations Volume';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '320px';

    public ?string $filter = 'monthly';

    /**
     * Check if the authenticated user has permission to view this widget.
     * The widget is visible if the user can view Master Bills OR House Bills.
     */
    public static function canView(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->can('viewAny', MasterBill::class) || $user->can('viewAny', HouseBill::class);
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
     * Dynamic description displaying aggregate shipment volume for authorized entities.
     */
    public function getDescription(): ?string
    {
        $user = auth()->user();
        $canViewMbl = $user?->can('viewAny', MasterBill::class) ?? false;
        $canViewHbl = $user?->can('viewAny', HouseBill::class) ?? false;

        if (! $canViewMbl && ! $canViewHbl) {
            return null;
        }

        $aggregates = $this->calculateAggregates($canViewMbl, $canViewHbl);

        $filterLabel = match ($this->filter) {
            'quarterly' => 'This Year (Quarterly)',
            'yearly' => 'Last 5 Years (Yearly)',
            default => 'This Year (Monthly)',
        };

        $parts = [];
        if ($canViewMbl) {
            $parts[] = 'Master Bills (MBL): '.number_format($aggregates['total_mbl']);
        }
        if ($canViewHbl) {
            $parts[] = 'House Bills (HBL): '.number_format($aggregates['total_hbl']);
        }
        if ($canViewMbl && $canViewHbl) {
            $parts[] = 'Total Volume: '.number_format($aggregates['total_ops']);
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
                    'ticks' => [
                        'precision' => 0,
                    ],
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
        $canViewMbl = $user?->can('viewAny', MasterBill::class) ?? false;
        $canViewHbl = $user?->can('viewAny', HouseBill::class) ?? false;

        $filter = $this->filter ?? 'monthly';

        return match ($filter) {
            'quarterly' => $this->getQuarterlyData($canViewMbl, $canViewHbl),
            'yearly' => $this->getYearlyData($canViewMbl, $canViewHbl),
            default => $this->getMonthlyData($canViewMbl, $canViewHbl),
        };
    }

    /**
     * Get monthly breakdown for current year.
     *
     * @return array{datasets: array<int, array<string, mixed>>, labels: array<int, string>}
     */
    protected function getMonthlyData(bool $canViewMbl, bool $canViewHbl): array
    {
        $currentYear = now()->year;
        $startDate = Carbon::create($currentYear, 1, 1)->startOfDay();
        $endDate = Carbon::create($currentYear, 12, 31)->endOfDay();

        $mblData = [];
        $hblData = [];
        $totalData = [];

        if ($canViewMbl) {
            $masterBills = $this->queryMasterBills($startDate, $endDate);
            $mblMonthly = array_fill(1, 12, 0);

            foreach ($masterBills as $bill) {
                $month = (int) Carbon::parse($bill->date)->format('n');
                $mblMonthly[$month]++;
            }

            for ($m = 1; $m <= 12; $m++) {
                $mblData[] = $mblMonthly[$m];
            }
        }

        if ($canViewHbl) {
            $houseBills = $this->queryHouseBills($startDate, $endDate);
            $hblMonthly = array_fill(1, 12, 0);

            foreach ($houseBills as $bill) {
                $month = (int) Carbon::parse($bill->created_at)->format('n');
                $hblMonthly[$month]++;
            }

            for ($m = 1; $m <= 12; $m++) {
                $hblData[] = $hblMonthly[$m];
            }
        }

        if ($canViewMbl && $canViewHbl) {
            for ($m = 0; $m < 12; $m++) {
                $totalData[] = $mblData[$m] + $hblData[$m];
            }
        }

        $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        return [
            'datasets' => $this->buildDatasets($mblData, $hblData, $totalData, $canViewMbl, $canViewHbl),
            'labels' => $labels,
        ];
    }

    /**
     * Get quarterly breakdown for current year.
     *
     * @return array{datasets: array<int, array<string, mixed>>, labels: array<int, string>}
     */
    protected function getQuarterlyData(bool $canViewMbl, bool $canViewHbl): array
    {
        $currentYear = now()->year;
        $startDate = Carbon::create($currentYear, 1, 1)->startOfDay();
        $endDate = Carbon::create($currentYear, 12, 31)->endOfDay();

        $mblData = [];
        $hblData = [];
        $totalData = [];

        if ($canViewMbl) {
            $masterBills = $this->queryMasterBills($startDate, $endDate);
            $mblQuarterly = array_fill(1, 4, 0);

            foreach ($masterBills as $bill) {
                $quarter = (int) ceil(((int) Carbon::parse($bill->date)->format('n')) / 3);
                $mblQuarterly[$quarter]++;
            }

            for ($q = 1; $q <= 4; $q++) {
                $mblData[] = $mblQuarterly[$q];
            }
        }

        if ($canViewHbl) {
            $houseBills = $this->queryHouseBills($startDate, $endDate);
            $hblQuarterly = array_fill(1, 4, 0);

            foreach ($houseBills as $bill) {
                $quarter = (int) ceil(((int) Carbon::parse($bill->created_at)->format('n')) / 3);
                $hblQuarterly[$quarter]++;
            }

            for ($q = 1; $q <= 4; $q++) {
                $hblData[] = $hblQuarterly[$q];
            }
        }

        if ($canViewMbl && $canViewHbl) {
            for ($q = 0; $q < 4; $q++) {
                $totalData[] = $mblData[$q] + $hblData[$q];
            }
        }

        $labels = ['Q1 (Jan - Mar)', 'Q2 (Apr - Jun)', 'Q3 (Jul - Sep)', 'Q4 (Oct - Dec)'];

        return [
            'datasets' => $this->buildDatasets($mblData, $hblData, $totalData, $canViewMbl, $canViewHbl),
            'labels' => $labels,
        ];
    }

    /**
     * Get 5-year trend breakdown.
     *
     * @return array{datasets: array<int, array<string, mixed>>, labels: array<int, string>}
     */
    protected function getYearlyData(bool $canViewMbl, bool $canViewHbl): array
    {
        $currentYear = now()->year;
        $startYear = $currentYear - 4;

        $startDate = Carbon::create($startYear, 1, 1)->startOfDay();
        $endDate = Carbon::create($currentYear, 12, 31)->endOfDay();

        $mblData = [];
        $hblData = [];
        $totalData = [];
        $labels = [];

        for ($year = $startYear; $year <= $currentYear; $year++) {
            $labels[] = (string) $year;
        }

        if ($canViewMbl) {
            $masterBills = $this->queryMasterBills($startDate, $endDate);
            $mblYearly = array_fill_keys(array_keys(array_flip($labels)), 0);

            foreach ($masterBills as $bill) {
                $year = (string) Carbon::parse($bill->date)->format('Y');
                if (isset($mblYearly[$year])) {
                    $mblYearly[$year]++;
                }
            }

            foreach ($labels as $year) {
                $mblData[] = $mblYearly[$year];
            }
        }

        if ($canViewHbl) {
            $houseBills = $this->queryHouseBills($startDate, $endDate);
            $hblYearly = array_fill_keys(array_keys(array_flip($labels)), 0);

            foreach ($houseBills as $bill) {
                $year = (string) Carbon::parse($bill->created_at)->format('Y');
                if (isset($hblYearly[$year])) {
                    $hblYearly[$year]++;
                }
            }

            foreach ($labels as $year) {
                $hblData[] = $hblYearly[$year];
            }
        }

        if ($canViewMbl && $canViewHbl) {
            $count = count($labels);
            for ($i = 0; $i < $count; $i++) {
                $totalData[] = $mblData[$i] + $hblData[$i];
            }
        }

        return [
            'datasets' => $this->buildDatasets($mblData, $hblData, $totalData, $canViewMbl, $canViewHbl),
            'labels' => $labels,
        ];
    }

    /**
     * Calculate summary aggregates for authorized entities.
     *
     * @return array{total_mbl: int, total_hbl: int, total_ops: int}
     */
    protected function calculateAggregates(bool $canViewMbl, bool $canViewHbl): array
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

        $totalMbl = 0;
        $totalHbl = 0;

        if ($canViewMbl) {
            $totalMbl = MasterBill::query()
                ->whereNull('deleted_at')
                ->whereBetween('date', [$startDate->toDateTimeString(), $endDate->toDateTimeString()])
                ->count();
        }

        if ($canViewHbl) {
            $totalHbl = HouseBill::query()
                ->whereNull('deleted_at')
                ->whereBetween('created_at', [$startDate->toDateTimeString(), $endDate->toDateTimeString()])
                ->count();
        }

        return [
            'total_mbl' => $totalMbl,
            'total_hbl' => $totalHbl,
            'total_ops' => $totalMbl + $totalHbl,
        ];
    }

    /**
     * Build Chart.js datasets array respecting permissions.
     *
     * @param  array<int, int>  $mblData
     * @param  array<int, int>  $hblData
     * @param  array<int, int>  $totalData
     * @return array<int, array<string, mixed>>
     */
    protected function buildDatasets(
        array $mblData,
        array $hblData,
        array $totalData,
        bool $canViewMbl,
        bool $canViewHbl
    ): array {
        $datasets = [];

        if ($canViewMbl) {
            $datasets[] = [
                'label' => 'Master Bills (MBL)',
                'data' => $mblData,
                'backgroundColor' => 'rgba(59, 130, 246, 0.8)',
                'borderColor' => '#2563eb',
                'borderWidth' => 1,
                'borderRadius' => 4,
            ];
        }

        if ($canViewHbl) {
            $datasets[] = [
                'label' => 'House Bills (HBL)',
                'data' => $hblData,
                'backgroundColor' => 'rgba(245, 158, 11, 0.8)',
                'borderColor' => '#d97706',
                'borderWidth' => 1,
                'borderRadius' => 4,
            ];
        }

        if ($canViewMbl && $canViewHbl) {
            $datasets[] = [
                'type' => 'line',
                'label' => 'Total Volume (MBL + HBL)',
                'data' => $totalData,
                'borderColor' => '#8b5cf6',
                'backgroundColor' => 'rgba(139, 92, 246, 0.15)',
                'borderWidth' => 2,
                'tension' => 0.25,
                'fill' => false,
            ];
        }

        return $datasets;
    }

    /**
     * Query Master Bills within date range.
     *
     * @return Collection<int, MasterBill>
     */
    protected function queryMasterBills(Carbon $startDate, Carbon $endDate): Collection
    {
        return MasterBill::query()
            ->whereNull('deleted_at')
            ->whereBetween('date', [$startDate->toDateTimeString(), $endDate->toDateTimeString()])
            ->select(['id', 'date'])
            ->toBase()
            ->get();
    }

    /**
     * Query House Bills within date range.
     *
     * @return Collection<int, HouseBill>
     */
    protected function queryHouseBills(Carbon $startDate, Carbon $endDate): Collection
    {
        return HouseBill::query()
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$startDate->toDateTimeString(), $endDate->toDateTimeString()])
            ->select(['id', 'created_at'])
            ->toBase()
            ->get();
    }
}
