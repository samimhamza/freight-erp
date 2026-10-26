<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Traits\DeleteTrait;
use App\Traits\LogTraits;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class RemittanceAdvice extends Model
{
    use DeleteTrait,
        HasFactory,
        LogsActivity,
        LogTraits,
        SoftDeletes;

    protected $table = 'remittance_advices';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::saving(function (RemittanceAdvice $advice) {
            if ($advice->master_bill_id && ! $advice->carrier_id) {
                $advice->carrier_id = $advice->masterBill?->carrier_id;
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept(['created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by', 'deleted_by'])
            ->logOnlyDirty()
            ->useLogName('RemittanceAdvice');
    }

    protected function casts(): array
    {
        return [
            'advice_date' => 'date',
            'due_date' => 'date',
            'status' => InvoiceStatus::class,
            'sent_at' => 'datetime',
        ];
    }

    /**
     * Backward-compatibility accessor and mutator for legacy invoice_number property.
     */
    protected function invoiceNumber(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->advice_number,
            set: fn ($value) => ['advice_number' => $value],
        );
    }

    /**
     * Backward-compatibility accessor and mutator for legacy invoice_date property.
     */
    protected function invoiceDate(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->advice_date,
            set: fn ($value) => ['advice_date' => $value],
        );
    }

    public function getTotalAttribute()
    {
        return $this->items()
            ->selectRaw('SUM((quantity * amount) + vat) as total')
            ->value('total');
    }

    public function getTotalPaidAttribute()
    {
        return $this->payments()
            ->selectRaw('SUM(amount) as total_paid')
            ->value('total_paid');
    }

    public function getTotalRemainingAttribute()
    {
        $total = $this->total ?? 0;
        $paid = $this->total_paid ?? 0;

        return $total - $paid;
    }

    public function masterBill(): BelongsTo
    {
        return $this->belongsTo(MasterBill::class);
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(RemittancePayment::class, 'remittance_advice_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RemittanceAdviceItem::class, 'remittance_advice_id');
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

    public static function updateStatusOnSaveChild($record): void
    {
        $advice = $record->remittanceAdvice ?? $record->remittanceInvoice;

        if (! $advice) {
            return;
        }

        if ($advice->total_remaining == 0) {
            $advice->update([
                'status' => InvoiceStatus::PAID,
            ]);
        } elseif ($advice->total_remaining > 0 && $advice->total_paid > 0) {
            $advice->update([
                'status' => InvoiceStatus::PARTIALLY_PAID,
            ]);
        }
    }

    public static function updateStatusOnDeleteChild($record): void
    {
        $advice = $record->remittanceAdvice ?? $record->remittanceInvoice;

        if (! $advice) {
            return;
        }

        if ($advice->payments()->count() == 1) {
            $advice->update([
                'status' => InvoiceStatus::SENT,
            ]);
        } elseif ($advice->total_paid - $record->amount !== 0) {
            $advice->update([
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

    public function canBeSent(): bool
    {
        if ($this->trashed()) {
            return false;
        }

        $email = config('company.remittance_advice_email')
            ?? config('company.remittance_invoice_email')
            ?? config('company.payable_invoice_email');

        if (! filled($email)) {
            return false;
        }

        return ! in_array($this->status, [InvoiceStatus::PAID, InvoiceStatus::CANCELLED], true);
    }
}
