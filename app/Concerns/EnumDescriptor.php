<?php

namespace App\Concerns;

trait EnumDescriptor
{

    public static function getPairs(): array
    {
        return collect(self::getKeys())->mapWithKeys(fn($key) => [self::getValue($key) => self::fromKey($key)->description])->toArray();
    }
}
