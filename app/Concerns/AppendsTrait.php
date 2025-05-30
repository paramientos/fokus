<?php

namespace App\Concerns;

trait AppendsTrait
{

    public static bool $withoutAppends = false;


    protected function getArrayableAppends()
    {
        if (self::$withoutAppends) {
            return [];
        }
        return parent::getArrayableAppends();
    }
}
