<?php

namespace App\Concerns;

use Illuminate\Contracts\Database\Eloquent\Builder;

trait UseMaryUIChoice
{
    public function generateMaryUIChoice(
        string   $key = 'id',
        string   $label = 'name',
        string   $orderBy = 'order',
        string   $direction = 'asc',
        callable $query = null
    ): array
    {
        /** @var Builder $builder */
        $builder = $this->query();

        if ($query !== null) {
            $query($builder);
        }

        return $builder
            ->orderBy($orderBy, $direction)
            ->get()
            ->map(function ($model) use ($key, $label) {
                return [
                    'id' => $model->{$key},
                    'name' => $model->{$label},
                ];
            })
            ->toArray();
    }
}
