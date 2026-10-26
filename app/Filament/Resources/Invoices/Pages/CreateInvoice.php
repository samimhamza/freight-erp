<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Filament\Resources\Invoices\InvoiceResource;
use Filament\Resources\Pages\Concerns\HasWizard;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoice extends CreateRecord
{
    use HasWizard;

    protected static string $resource = InvoiceResource::class;

    protected function getSteps(): array
    {
        return InvoiceResource::getSteps();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = InvoiceStatus::DRAFT;

        if (isset($data['house_bill_id']) && is_string($data['house_bill_id']) && str_starts_with($data['house_bill_id'], 'custom:')) {
            $data['house_bill'] = str($data['house_bill_id'])->after('custom:')->trim()->toString();
            $data['house_bill_id'] = null;
        }

        $type = $data['type'] ?? null;
        $isShipping = ($type instanceof InvoiceType) ? $type === InvoiceType::SHIPPING : $type === InvoiceType::SHIPPING->value;

        $source = $data['house_bill_source'] ?? null;
        unset($data['house_bill_source']);

        if ($isShipping) {
            $data['house_bill'] = null;
            $data['container_numbers'] = null;
        } elseif ($source === 'manual' || (blank($data['house_bill_id'] ?? null) && filled($data['house_bill'] ?? null))) {
            $data['house_bill_id'] = null;
            $data['house_bill'] = filled($data['house_bill'] ?? null) ? trim((string) $data['house_bill']) : null;
            $data['container_numbers'] = is_array($data['container_numbers'] ?? null) ? array_values(array_filter($data['container_numbers'])) : null;
        } else {
            $data['house_bill'] = null;
            $data['container_numbers'] = null;
        }

        return $data;
    }
}
