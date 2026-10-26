<?php

namespace App\Models;

use App\Enums\VehicleStatus;
use App\Enums\VehicleType;
use App\Traits\DeleteTrait;
use App\Traits\LogTraits;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Vehicle extends Model
{
    use DeleteTrait,
        HasFactory,
        LogsActivity,
        LogTraits,
        SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'status' => VehicleStatus::class,
        'type' => VehicleType::class,
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept(['created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by', 'deleted_by'])
            ->logOnlyDirty()
            ->useLogName('Vehicle');
    }

    public function houseBillContainer(): BelongsTo
    {
        return $this->belongsTo(HouseBillContainer::class, 'house_bill_container_id');
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

    public static function getStatusColor($status): string
    {
        return match ($status) {
            VehicleStatus::NEW => 'success',
            VehicleStatus::USED => 'info',
        };
    }

    public static function getTypeColor($type): string
    {
        return match ($type) {
            VehicleType::CAR => 'primary',
            VehicleType::BIKE => 'danger',
        };
    }
}
