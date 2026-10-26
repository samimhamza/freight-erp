<?php

namespace App\Traits;

trait LogTraits
{
    public static function bootLogTraits()
    {
        // updating created_by and updated_by when model is created
        static::creating(function ($model) {
            $user = auth()->user(); // Get authenticated user

            if ($user) {
                if (!$model->isDirty('created_by')) {
                    $model->created_by = $user->id;
                }
                if (!$model->isDirty('updated_by')) {
                    $model->updated_by = $user->id;
                }
            }
        });

        // updating updated_by when model is updated
        static::updating(function ($model) {
            $user = auth()->user(); // Get authenticated user

            if ($user && $model->isDirty()) {
                $model->updated_by = $user->id;
            }
        });
    }
}
