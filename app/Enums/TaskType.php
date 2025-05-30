<?php

namespace App\Enums;

use App\Concerns\EnumToArray;

enum TaskType: string
{
    use EnumToArray;

    case TASK = 'task';
    case BUG = 'bug';
    case FEATURE = 'feature';
    case IMPROVEMENT = 'improvement';

    /**
     * Get the label for the enum value.
     */
    public function label(): string
    {
        return match ($this) {
            self::TASK => 'Task',
            self::BUG => 'Bug',
            self::FEATURE => 'Feature',
            self::IMPROVEMENT => 'Improvement',
        };
    }

    /**
     * Get all enum values as an array for Mary UI select component.
     */
    public static function toArray(): array
    {
        return [
            self::TASK->value => self::TASK->label(),
            self::BUG->value => self::BUG->label(),
            self::FEATURE->value => self::FEATURE->label(),
            self::IMPROVEMENT->value => self::IMPROVEMENT->label(),
        ];
    }

    public function icon(): string
    {
        return match ($this) {
            self::TASK => 'check',
            self::BUG => 'check',
            self::FEATURE => 'check',
            self::IMPROVEMENT => 'check',
        };
    }
}
