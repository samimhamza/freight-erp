<?php

namespace App\Models;

use App\Filament\Resources\Charges\Schemas\ChargeForm;
use App\Traits\DeleteTrait;
use App\Traits\LogTraits;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class RemittanceAdviceItem extends Model
{
    use DeleteTrait,
        HasFactory,
        LogsActivity,
        LogTraits,
        SoftDeletes;

    protected $table = 'remittance_advice_items';

    protected $guarded = ['id'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept(['created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by', 'deleted_by'])
            ->logOnlyDirty()
            ->useLogName('Remittance Advice Item');
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:3',
        ];
    }

    public function remittanceAdvice(): BelongsTo
    {
        return $this->belongsTo(RemittanceAdvice::class, 'remittance_advice_id');
    }

    public function remittanceInvoice(): BelongsTo
    {
        return $this->remittanceAdvice();
    }

    public function payableInvoice(): BelongsTo
    {
        return $this->remittanceAdvice();
    }

    public function charge(): BelongsTo
    {
        return $this->belongsTo(Charge::class);
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

    public static function form($showRemittanceAdvice = false): array
    {
        return [
            Select::make('remittance_advice_id')
                ->relationship('remittanceAdvice', 'advice_number')
                ->preload()
                ->searchable()
                ->required()
                ->visible($showRemittanceAdvice),
            Select::make('charge_id')
                ->relationship('charge', 'name')
                ->createOptionForm(ChargeForm::components())
                ->preload()
                ->searchable()
                ->required(),
            TextInput::make('quantity')
                ->required()
                ->numeric()
                ->default(1),
            TextInput::make('amount')
                ->required()
                ->numeric()
                ->suffix('AED')
                ->inputMode('decimal'),
            TextInput::make('vat')
                ->numeric()
                ->suffix('AED')
                ->default(0),
        ];
    }

    public static function columns(): array
    {
        return [
            TextColumn::make('remittanceAdvice.advice_number')
                ->label('Advice #')
                ->searchable()
                ->sortable(),
            TextColumn::make('charge.name')
                ->searchable()
                ->sortable(),
            TextColumn::make('quantity')
                ->sortable(),
            TextColumn::make('amount')
                ->numeric(decimalPlaces: 2)
                ->suffix(' AED')
                ->sortable(),
            TextColumn::make('vat')
                ->numeric(decimalPlaces: 2)
                ->suffix(' AED')
                ->sortable(),
        ];
    }
}
