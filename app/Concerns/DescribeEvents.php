<?php

namespace App\Concerns;

trait DescribeEvents
{
    public function isAnIncidentEvent(): bool
    {
        return !empty($this->incidentEvent) && $this->occurredEvent === $this->incidentEvent;
    }

    public function isAHealthyEvent(): bool
    {
        return !empty($this->resolveEvent) && $this->occurredEvent === $this->resolveEvent;
    }
}
