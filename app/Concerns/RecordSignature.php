<?php

namespace App\Concerns;


use Illuminate\Support\Facades\Auth;

trait RecordSignature
{
    protected static function bootRecordSignature()
    {
        static::updating(function ($model) {
            parent::boot();
            if (isset($model->updated_by)) {
                $model->updated_by = Auth::user()->id;
            }
        });

        static::creating(function ($model) {
            parent::boot();
            if (isset($model->created_by)) {
                $model->created_by = Auth::user()->id;
            }
        });
    }

}
