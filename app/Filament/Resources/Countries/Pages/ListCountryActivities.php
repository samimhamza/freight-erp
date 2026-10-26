<?php

namespace App\Filament\Resources\Countries\Pages;

use App\Filament\Resources\Countries\CountryResource;
use pxlrbt\FilamentActivityLog\Pages\ListActivities;

class ListCountryActivities extends ListActivities
{
    protected static string $resource = CountryResource::class;
}
