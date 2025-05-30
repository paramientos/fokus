<?php

namespace App\Enums;

use App\Concerns\EnumToArray;

enum Priority: int
{
    use EnumToArray;

    case LOW = 1;
    case MEDIUM = 2;
    case HIGH = 3;

    /**
     * Get the label for the enum value.
     */
    public function label(): string
    {
        return match ($this) {
            self::LOW => 'Low',
            self::MEDIUM => 'Medium',
            self::HIGH => 'High',
        };
    }

    /**
     * Get all enum values as an array for Mary UI select component.
     */
    public static function toArray(): array
    {
        return [
            self::LOW->value => self::LOW->label(),
            self::MEDIUM->value => self::MEDIUM->label(),
            self::HIGH->value => self::HIGH->label(),
        ];
    }

    public function color(): string
    {
        return match ($this) {
            self::LOW => 'green',
            self::MEDIUM => 'yellow',
            self::HIGH => 'red',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::LOW => 'check',
            self::MEDIUM => 'check',
            self::HIGH => 'check',
        };
    }
}
