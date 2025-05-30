<?php

namespace App\Concerns;

trait UserTimezone
{
    public ?string $timezone = null;
    public ?string $dateTimeFormat = null;

    public function loadTimezone()
    {
        $this->timezone = user_timezone_or_default(auth('admin')->id());
        $this->dateTimeFormat = config('my.date_time_format');
    }
}
