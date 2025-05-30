<?php

namespace App\Concerns;

trait Radio
{
    public function getYesOrNoOptions(): array
    {
        return [true => 'Evet', false => 'Hayır'];
    }

    public function getStatusOptions(): array
    {
        return [true => 'Aktif', false => 'Pasif'];
    }

    public function chooseYesOrNoOptions($value): string
    {
        return $value === true ? 'Evet' : 'Hayır';
    }

}
