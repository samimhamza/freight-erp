<?php

namespace App\Traits;

trait DeleteTrait
{
    protected static function bootDeleteTrait()
    {
        static::deleting(function ($model) {
            if (method_exists($model, 'isForceDeleting') && $model->isForceDeleting()) {
                return;
            }

            if (auth()->check() && (in_array('deleted_by', $model->getFillable()) || $model->isFillable('deleted_by'))) {
                $model->deleted_by = auth()->id();
                $model->saveQuietly();
            }
        });

        static::restoring(function ($model) {
            if (in_array('deleted_by', $model->getFillable()) || $model->isFillable('deleted_by')) {
                $model->deleted_by = null;
            }
        });
    }
}
