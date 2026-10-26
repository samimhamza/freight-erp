<?php

namespace App\Models;

use App\Traits\DeleteTrait;
use App\Traits\HasTimestampsAndUsers;
use App\Traits\LogTraits;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Payment extends Model
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
            ->useLogName('Payment');
    }

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
            'amount' => 'decimal:3',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
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

    public static function form($maxValue, $minDate = null): array
    {
        return [
            Grid::make(2)
                ->schema([
                    TextInput::make('payment_reference')
                        ->label('Payment Reference')
                        ->required()
                        ->maxLength(64),
                    DatePicker::make('date')
                        ->maxDate(now())
                        ->minDate($minDate)
                        ->required(),
                    TextInput::make('amount')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->maxValue($maxValue),
                ]),
        ];
    }

    public static function columns(): array
    {
        return [
            TextColumn::make('payment_number')
                ->searchable()
                ->sortable(),
            TextColumn::make('payment_reference')
                ->label('Payment Reference')
                ->searchable()
                ->sortable(),
            TextColumn::make('date')
                ->dateTime('Y-m-d h:i A')
                ->sortable(),
            TextColumn::make('amount')
                ->numeric(decimalPlaces: 2)
                ->suffix(' AED')
                ->sortable(),
            ...HasTimestampsAndUsers::timestampsWithUsers(false),
        ];
    }
}
