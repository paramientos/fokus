<?php

namespace App\Concerns;

use DateTimeZone;

trait Timezone
{
    public function loadTimezoneAsList(): array
    {
        $timezones = [];

        foreach (DateTimeZone::listIdentifiers() as $tz) {
            $timezones[$tz] = $tz;
        }

        return $timezones;
    }
}
