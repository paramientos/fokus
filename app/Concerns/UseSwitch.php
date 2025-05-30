<?php

namespace App\Concerns;

trait UseSwitch
{
    public function getOptions(): array
    {
        return [
            'on' => ['value' => 1, 'text' => 'Evet', 'color' => 'primary'],
            'off' => ['value' => 0, 'text' => 'Hayır', 'color' => 'default'],
        ];
    }
}
