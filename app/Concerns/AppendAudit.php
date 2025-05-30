<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Model;

trait AppendAudit
{

    public static function bootAppendAudit()
    {
        if (auth()->check()) {
            static::creating(function (Model $model) {
                parent::boot();

                if (!empty($model->created_by)) {
                    $model->created_by = auth()->id();
                }
            });

            static::updating(function (Model $model) {
                parent::boot();

                if (!empty($model->updated_by)) {
                    $model->updated_by = auth()->id();
                }
            });
        }
    }
}
