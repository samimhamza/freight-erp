<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use pxlrbt\FilamentActivityLog\Pages\ListActivities;

class ListInvoiceActivities extends ListActivities
{
    protected static string $resource = InvoiceResource::class;
}
