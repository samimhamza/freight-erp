<?php

namespace App\Models;

use App\Enums\HouseBillType;
use App\Traits\DeleteTrait;
use App\Traits\LogTraits;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class HouseBill extends Model
{
    use DeleteTrait,
        HasFactory,
        LogsActivity,
        LogTraits,
        SoftDeletes;

    protected $guarded = ['id'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept(['created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by', 'deleted_by'])
            ->logOnlyDirty()
            ->useLogName('HouseBill');
    }

    protected function casts(): array
    {
        return [
            'type' => HouseBillType::class,
            'arrival_notice_sent_at' => 'datetime',
        ];
    }

    public function canPrintArrivalNotice(): bool
    {
        return ! $this->trashed()
            && $this->masterBill !== null
            && $this->masterBill->ata !== null;
    }

    public function canSendArrivalNotice(): bool
    {
        return ! $this->trashed()
            && filled($this->customer?->email)
            && $this->masterBill !== null
            && $this->masterBill->ata !== null
            && now()->startOfDay()->lte($this->masterBill->ata->copy()->startOfDay()->addDays(14));
    }

    public function vehicles()
    {
        return $this->hasManyThrough(Vehicle::class, HouseBillContainer::class);
    }

    public function masterBill(): BelongsTo
    {
        return $this->belongsTo(MasterBill::class);
    }

    public function containers(): HasMany
    {
        return $this->hasMany(HouseBillContainer::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function notifyParty(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function prints(): MorphMany
    {
        return $this->morphMany(ModalPrint::class, 'printable');
    }

    public function consignor(): BelongsTo
    {
        return $this->belongsTo(Consignor::class, 'consignor_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function getForceDeleteRestrictions(): array
    {
        $reasons = [];
        $containersCount = $this->containers()->withTrashed()->count();
        if ($containersCount > 0) {
            $reasons[] = "{$containersCount} Container(s)";
        }

        $invoicesCount = $this->invoices()->withTrashed()->count();
        if ($invoicesCount > 0) {
            $reasons[] = "{$invoicesCount} Invoice(s)";
        }

        return $reasons;
    }

    public function getTotalContainersAttribute(): int
    {
        if ($this->relationLoaded('containers')) {
            return $this->containers->count();
        }

        return (int) ($this->containers_count ?? $this->containers()->count());
    }

    public function getTotalVehiclesAttribute()
    {
        if ($this->relationLoaded('containers')) {
            return (int) $this->containers->sum(fn ($c) => $c->relationLoaded('vehicles') ? $c->vehicles->count() : $c->vehicles()->count());
        }

        return $this->vehicles()->count();
    }

    public function getTotalWeightAttribute()
    {
        if ($this->relationLoaded('containers')) {
            return (float) $this->containers->sum('gross_weight');
        }

        return (float) ($this->newQuery()
            ->join('house_bill_containers', 'house_bills.id', '=', 'house_bill_containers.house_bill_id')
            ->where('house_bills.id', $this->id)
            ->whereNull('house_bills.deleted_at')
            ->whereNull('house_bill_containers.deleted_at')
            ->sum('house_bill_containers.gross_weight') ?? 0);
    }

    public function getTotalPackagesAttribute()
    {
        if ($this->relationLoaded('containers')) {
            return (int) $this->containers->sum('packages');
        }

        return (int) ($this->newQuery()
            ->join('house_bill_containers', 'house_bills.id', '=', 'house_bill_containers.house_bill_id')
            ->where('house_bills.id', $this->id)
            ->whereNull('house_bills.deleted_at')
            ->whereNull('house_bill_containers.deleted_at')
            ->sum('house_bill_containers.packages') ?? 0);
    }

    public function getTotalVolumeAttribute()
    {
        if ($this->relationLoaded('containers')) {
            return (float) $this->containers->sum(fn ($c) => (float) ($c->container?->volume ?? 0));
        }

        return (float) ($this->newQuery()
            ->join('house_bill_containers', 'house_bills.id', '=', 'house_bill_containers.house_bill_id')
            ->join('containers', 'containers.id', '=', 'house_bill_containers.container_id')
            ->where('house_bills.id', $this->id)
            ->whereNull('house_bills.deleted_at')
            ->whereNull('house_bill_containers.deleted_at')
            ->whereNull('containers.deleted_at')
            ->sum('containers.volume') ?? 0);
    }
}
