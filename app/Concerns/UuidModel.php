<?php

namespace App\Concerns;

use Illuminate\Support\Str;

trait UuidModel
{

    public static function bootUuidModel()
    {
        static::creating(function ($model) {
            parent::boot();

            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = Str::uuid();
            }
        });
    }

    public function getIncrementing(): bool
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'string';
    }

    public function getCasts()
    {
        return [
            'id' => 'string'
        ];
    }
}
