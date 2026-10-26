<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use pxlrbt\FilamentActivityLog\Pages\ListActivities;

class ListCustomerActivities extends ListActivities
{
    protected static string $resource = CustomerResource::class;
}
