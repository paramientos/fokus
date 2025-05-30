<?php

namespace App\Concerns;

trait Paginatable
{
    protected $perPageMax = 1000;

    public function getPerPage(): int
    {
        $perPage = request('perPage', $this->perPage);

        if ($perPage === 'all') {
            $perPage = $this->count();
        }

        return max(1, min($this->perPageMax, (int)$perPage));
    }

    public function setPerPageMax(int $perPageMax): void
    {
        $this->perPageMax = $perPageMax;
    }
}
