<?php

namespace App\Filament\Resources\RemittanceAdvices\Pages;

use App\Models\RemittanceAdviceItem;

class ListRemittanceAdviceItemActivities extends ListRemittanceAdviceChildActivities
{
    protected static function childModelClass(): string
    {
        return RemittanceAdviceItem::class;
    }

    protected function formSchemaComponents(): array
    {
        return RemittanceAdviceItem::form(true);
    }

    public function getRecordTitle(): string
    {
        $record = $this->getRecord();

        return 'Remittance Advice Item #'.$record->id;
    }
}
