<?php

namespace App\Filament\Resources\RemittanceAdvices\Pages;

use App\Filament\Resources\RemittanceAdvices\RemittanceAdviceResource;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\MorphToSelect;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use pxlrbt\FilamentActivityLog\Pages\ListActivitiesBySubject;

abstract class ListRemittanceAdviceChildActivities extends ListActivitiesBySubject
{
    protected static string $resource = RemittanceAdviceResource::class;

    abstract protected static function childModelClass(): string;

    /**
     * @return array<int, mixed>
     */
    abstract protected function formSchemaComponents(): array;

    protected function resolveRecord(int|string $key): Model
    {
        return static::childModelClass()::withTrashed()->findOrFail($key);
    }

    protected function createFieldLabelMap(): Collection
    {
        $schema = new Schema($this);
        $schema->components($this->formSchemaComponents());

        $components = collect($schema->getComponents());
        $extracted = collect();

        while (($component = $components->shift()) !== null) {
            if ($component instanceof Field || $component instanceof MorphToSelect) {
                $extracted->push($component);

                continue;
            }

            $children = $component->getChildComponents();

            if (count($children) > 0) {
                $components = $components->merge($children);

                continue;
            }

            $extracted->push($component);
        }

        return $extracted
            ->filter(fn ($field) => $field instanceof Field)
            ->mapWithKeys(fn (Field $field) => [
                $field->getName() => $field->getLabel(),
            ]);
    }
}
