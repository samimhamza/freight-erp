<?php

namespace App\Traits;

trait DeleteTrait
{
    public static function bootDeleteTrait()
    {
        static::deleting(function ($model) {
            $model->deleted_by = @auth()->user()->id;
            $model->save();
        });

        static::restoring(function ($model) {
            $model->deleted_by = null;
        });
    }
}
