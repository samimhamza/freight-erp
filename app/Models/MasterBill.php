<?php

namespace App\Models;

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

class MasterBill extends Model
{
    use DeleteTrait,
        HasFactory,
        LogsActivity,
        LogTraits,
        SoftDeletes;

    private const EOL = "\r\n";

    protected $guarded = ['id'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept(['created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by', 'deleted_by'])
            ->logOnlyDirty()
            ->useLogName('MasterBill');
    }

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
            'etd' => 'date',
            'eta' => 'date',
            'ata' => 'date',
        ];
    }

    public function canSendArrivalNotices(): bool
    {
        return ! $this->trashed()
            && $this->ata !== null
            && now()->startOfDay()->lte($this->ata->copy()->startOfDay()->addDays(14))
            && $this->houseBills()->exists();
    }

    public function houseBills(): HasMany
    {
        return $this->hasMany(HouseBill::class);
    }

    public function remittanceAdvices(): HasMany
    {
        return $this->hasMany(RemittanceAdvice::class);
    }

    public function remittanceInvoices(): HasMany
    {
        return $this->remittanceAdvices();
    }

    public function payableInvoices(): HasMany
    {
        return $this->remittanceAdvices();
    }

    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class, 'vessel_id');
    }

    public function exportAgent(): BelongsTo
    {
        return $this->belongsTo(ExportAgent::class, 'export_agent_id');
    }

    public function importAgent(): BelongsTo
    {
        return $this->belongsTo(ImportAgent::class, 'import_agent_id');
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class, 'carrier_id');
    }

    public function getExportAgentCompanyAttribute(): ?string
    {
        return $this->exportAgent?->name;
    }

    public function getExportAgentAddressAttribute(): ?string
    {
        return $this->exportAgent?->address;
    }

    public function getExportAgentEmailAttribute(): ?string
    {
        return $this->exportAgent?->email;
    }

    public function getExportAgentPhoneAttribute(): ?string
    {
        return $this->exportAgent?->phone;
    }

    public function getExportAgentFaxAttribute(): ?string
    {
        return $this->exportAgent?->fax;
    }

    public function getImportAgentCompanyAttribute(): ?string
    {
        return $this->importAgent?->name;
    }

    public function getImportAgentAddressAttribute(): ?string
    {
        return $this->importAgent?->address;
    }

    public function getImportAgentEmailAttribute(): ?string
    {
        return $this->importAgent?->email;
    }

    public function getImportAgentPhoneAttribute(): ?string
    {
        return $this->importAgent?->phone;
    }

    public function getImportAgentFaxAttribute(): ?string
    {
        return $this->importAgent?->fax;
    }

    public function origin(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'origin_id');
    }

    public function portOfDischarge(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'port_of_discharge_id');
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'destination_id');
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

    public function getTotalContainersAttribute()
    {
        return Container::query()
            ->join('house_bill_containers', 'house_bill_containers.container_id', '=', 'containers.id')
            ->join('house_bills', 'house_bill_containers.house_bill_id', '=', 'house_bills.id')
            ->where('house_bills.master_bill_id', $this->id)
            ->whereNull('house_bills.deleted_at')
            ->whereNull('house_bill_containers.deleted_at')
            ->count();
    }

    public function getTotalVehiclesAttribute()
    {
        return Vehicle::query()
            ->join('house_bill_containers', 'vehicles.house_bill_container_id', '=', 'house_bill_containers.id')
            ->join('house_bills', 'house_bill_containers.house_bill_id', '=', 'house_bills.id')
            ->where('house_bills.master_bill_id', $this->id)
            ->whereNull('house_bills.deleted_at')
            ->whereNull('house_bill_containers.deleted_at')
            ->count();
    }

    public function getTotalWeightAttribute()
    {
        return $this->join('house_bills', 'master_bills.id', '=', 'house_bills.master_bill_id')
            ->join('house_bill_containers', 'house_bills.id', '=', 'house_bill_containers.house_bill_id')
            ->whereNull('house_bills.deleted_at')
            ->sum('house_bill_containers.gross_weight');
    }

    public function getTotalVolumeAttribute()
    {
        return $this->join('house_bills', 'master_bills.id', '=', 'house_bills.master_bill_id')
            ->join('house_bill_containers', 'house_bills.id', '=', 'house_bill_containers.house_bill_id')
            ->join('containers', 'house_bill_containers.container_id', '=', 'containers.id')
            ->whereNull('house_bills.deleted_at')
            ->whereNull('house_bill_containers.deleted_at')
            ->whereNull('containers.deleted_at')
            ->sum('containers.volume');
    }

    public function getTotalPackagesAttribute()
    {
        return $this->join('house_bills', 'master_bills.id', '=', 'house_bills.master_bill_id')
            ->join('house_bill_containers', 'house_bills.id', '=', 'house_bill_containers.house_bill_id')
            ->whereNull('house_bills.deleted_at')
            ->whereNull('house_bill_containers.deleted_at')
            ->sum('house_bill_containers.packages');
    }

    public static function getTxtContent($record): string
    {
        $vessel = $record->vessel->name;
        $voyage = $record->vessel->voyage;
        $position = $record->vessel
            ->masterbills()
            ->orderBy('created_at')
            ->pluck('id')
            ->search($record->id) + 1;
        $ata = $record->ata?->format('d-M-Y') ?? $record->eta->format('d-M-Y');
        $firstLine = [$vessel, $voyage, $record->port_of_discharge?->code ?? $record->port_of_loading?->code, $ata, '', 'MFI', $position];

        $content = '"VOY","LINE CODE","",';
        $content .= '"'.implode('","', $firstLine).'"'.self::EOL;

        foreach ($record->houseBills as $houseBill) {
            $content .= self::getHouseBillTxtContent($record, $houseBill);
        }

        $content .= '"END","'.$record->houseBills->count().'","'.$record->houseBills->count().'"'.self::EOL;

        return $content;
    }

    public static function getHouseBillTxtContent($record, $houseBill): string
    {
        $fields = self::getHouseBillTxtFields($record, $houseBill);
        $content = '"'.implode('","', $fields).'"'.self::EOL;

        $content .= self::getContainersTxtDetails($houseBill);

        return $content;
    }

    public static function getContainersTxtDetails($houseBill): string
    {
        $containersDetails = '';
        foreach ($houseBill->containers as $key => $container) {
            $containerNumber = substr($container->container->container_number, 0, -1);
            $containerNumberLastCharacter = substr($container->container->container_number, -1);
            $packages = $container->packages;
            $vehicelsDetails = $container->container->vehicles?->count() > 0 ? ' & COMPLETE VEHICLE' : 'ONLY';
            $goodsDescriptions = '01 X '.$container->container->type->value.' CONTAINER STC: '.
                $packages.' X PACKAGES USED AUTO PARTS '.
                $vehicelsDetails.
                ', OTHER DETAILS AS PER BL';

            $fields = [
                'CTR',
                $containerNumber,
                $containerNumberLastCharacter,
                '00.0',
                $container->seal,
            ];
            $containersDetails .= '"'.implode('","', $fields).'"'.self::EOL;

            $secondFields = [
                'CON',
                $key + 1,
                $goodsDescriptions,
                '',
                '770000',
                "$packages",
                'PACKAGES',
                'PKG',
                '',
                $container->gross_weight,
                '000050.000',
                'N',
                '',
                '',
                '0000.0',
                '',
                'Y',
                'N',
                '0000.0',
                '0000.0',
                '',
            ];
            $containersDetails .= '"'.implode('","', $secondFields).'"'.self::EOL;
        }

        return $containersDetails;
    }

    public static function getHouseBillTxtFields($record, $houseBill): array
    {
        $origin = $record->origin->code;
        $portOfDischarge = $record->portOfDischarge?->code;
        $consignorCountryCode = $houseBill->consignor->country->code;
        $consignorName = $houseBill->consignor->name;
        $consignorAddress = $houseBill->consignor->address;
        $customerName = $houseBill->customer->name;
        $customerAddress = $houseBill->customer->address;
        $notifyName = $houseBill->notifyParty->name ?? 'SAME AS CONSIGNEE';
        $totalPackages = $houseBill->total_packages;
        $totalWeight = $houseBill->total_weight;

        return [
            'BOL',
            $houseBill->house_bill,
            'LINE CODE',
            '',
            $origin,
            $origin,
            $portOfDischarge,
            $portOfDischarge,
            '',
            '',
            'I',
            '',
            '',
            '',
            'F',
            'N',
            '',
            'FCL/FCL',
            $consignorCountryCode,
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            $consignorName,
            $consignorAddress,
            $consignorCountryCode,
            '',
            $customerName,
            $customerAddress,
            '',
            $notifyName,
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '770000',
            'GENERAL CARGO',
            $totalPackages,
            'PACKAGES',
            'PKG',
            '',
            '',
            '01',
            '0002',
            '0000.0',
            $totalWeight,
            $totalWeight,
            '000050.000',
            '0',
            '050.0',
            '1',
            '',
            '',
            'P',
        ];
    }
}
