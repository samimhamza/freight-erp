<?php

namespace App\Filament\Resources\RemittanceAdvices\Pages;

use App\Filament\Resources\RemittanceAdvices\RemittanceAdviceResource;
use pxlrbt\FilamentActivityLog\Pages\ListActivities;

class ListRemittanceAdviceActivities extends ListActivities
{
    protected static string $resource = RemittanceAdviceResource::class;
}
