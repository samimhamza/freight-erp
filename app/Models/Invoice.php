<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
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

class Invoice extends Model
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
            ->useLogName('Invoice');
    }

    protected function casts(): array
    {
        return [
            'type' => InvoiceType::class,
            'container_numbers' => 'array',
            'invoice_date' => 'date',
            'due_date' => 'date',
            'status' => InvoiceStatus::class,
            'sent_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (! isset($model->status)) {
                $model->status = InvoiceStatus::DRAFT;
            }
        });

        static::deleting(function (self $model): void {
            if ($model->isForceDeleting()) {
                $model->payments()->withTrashed()->forceDelete();
                $model->items()->withTrashed()->forceDelete();
            }
        });
    }

    public function getHouseBillNumberAttribute(): ?string
    {
        return $this->houseBill?->house_bill ?? $this->house_bill;
    }

    public function getAllContainerNumbersAttribute(): array
    {
        if ($this->houseBill) {
            $this->houseBill->loadMissing('containers.container');

            return $this->houseBill->containers
                ->map(fn ($hbc) => $hbc->container?->container_number)
                ->filter()
                ->values()
                ->all();
        }

        return array_values(array_filter((array) ($this->container_numbers ?? [])));
    }

    public function getHouseBillSelectionAttribute(): ?string
    {
        if ($this->house_bill_id) {
            return (string) $this->house_bill_id;
        }

        if (filled($this->house_bill)) {
            return 'custom:'.$this->house_bill;
        }

        return null;
    }

    public function setHouseBillIdAttribute($value): void
    {
        if (is_string($value) && str_starts_with($value, 'custom:')) {
            $this->attributes['house_bill_id'] = null;
            $this->attributes['house_bill'] = str($value)->after('custom:')->trim()->toString();
        } elseif (is_numeric($value)) {
            $this->attributes['house_bill_id'] = (int) $value;
            $this->attributes['house_bill'] = null;
        } else {
            $this->attributes['house_bill_id'] = $value ?: null;
        }
    }

    public function getTotalAttribute()
    {
        if ($this->relationLoaded('items')) {
            return (float) $this->items->sum(fn ($item) => ((float) $item->quantity * (float) $item->amount) + (float) $item->vat);
        }

        return (float) ($this->items()
            ->selectRaw('SUM((quantity * amount) + vat) as total')
            ->value('total') ?? 0.0);
    }

    public function getTotalPaidAttribute()
    {
        if ($this->relationLoaded('payments')) {
            return (float) $this->payments->sum('amount');
        }

        return (float) ($this->payments()
            ->selectRaw('SUM(amount) as total_paid')
            ->value('total_paid') ?? 0.0);
    }

    public function getTotalRemainingAttribute()
    {
        $total = $this->total ?? 0.0;
        $paid = $this->total_paid ?? 0.0;

        return $total - $paid;
    }

    public function houseBill(): BelongsTo
    {
        return $this->belongsTo(HouseBill::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function prints(): MorphMany
    {
        return $this->morphMany(ModalPrint::class, 'printable');
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

    public static function getChangableStatuses(): array
    {
        return [
            InvoiceStatus::DRAFT->value => InvoiceStatus::DRAFT->name,
            InvoiceStatus::CANCELLED->value => InvoiceStatus::CANCELLED->name,
        ];
    }

    public static function getStatusColor($status): string
    {
        return match ($status) {
            InvoiceStatus::DRAFT => 'gray',
            InvoiceStatus::SENT => 'info',
            InvoiceStatus::PARTIALLY_PAID => 'primary',
            InvoiceStatus::PAID => 'success',
            InvoiceStatus::OVERDUE => 'danger',
            InvoiceStatus::CANCELLED => 'gray',
        };
    }

    public static function updateStatusOnSaveChild($record)
    {
        if ($record->invoice->total_remaining == 0) {
            $record->invoice()->update([
                'status' => InvoiceStatus::PAID,
            ]);
        } elseif ($record->invoice->total_remaining > 0 && $record->invoice->total_paid > 0) {
            $record->invoice()->update([
                'status' => InvoiceStatus::PARTIALLY_PAID,
            ]);
        }
    }

    public static function updateStatusOnDeleteChild($record)
    {
        if ($record->invoice->payments()->count() == 1) {
            $record->invoice()->update([
                'status' => InvoiceStatus::SENT,
            ]);
        } elseif ($record->invoice->total_paid - $record->amount !== 0) {
            $record->invoice()->update([
                'status' => InvoiceStatus::PARTIALLY_PAID,
            ]);
        }
    }

    public static function showActions($record): bool
    {
        return $record->status === InvoiceStatus::DRAFT || $record->status === InvoiceStatus::CANCELLED || $record->status === InvoiceStatus::OVERDUE;
    }

    public static function showCreatePayment($record): bool
    {
        return $record->total_remaining > 0 && ($record->status === InvoiceStatus::SENT || $record->status === InvoiceStatus::PARTIALLY_PAID);
    }

    public static function showItemActions($record): bool
    {
        return $record->status === InvoiceStatus::DRAFT || $record->status === InvoiceStatus::OVERDUE;
    }

    public function canBeSentToCustomer(): bool
    {
        if ($this->trashed()) {
            return false;
        }

        if (! filled($this->customer?->email)) {
            return false;
        }

        return ! in_array($this->status, [InvoiceStatus::PAID, InvoiceStatus::CANCELLED], true);
    }
}
