<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Models\InvoiceItem;

class ListInvoiceItemActivities extends ListInvoiceChildActivities
{
    protected static function childModelClass(): string
    {
        return InvoiceItem::class;
    }

    protected function formSchemaComponents(): array
    {
        return InvoiceItem::form(true);
    }

    public function getRecordTitle(): string
    {
        $record = $this->getRecord();

        return 'Invoice Item #'.$record->id;
    }
}
