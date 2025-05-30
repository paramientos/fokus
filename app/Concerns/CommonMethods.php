<?php

namespace App\Concerns;

use OpenAdmin\Admin\Layout\Content;

trait CommonMethods
{
    public function show($id, Content $content): Content
    {
        return $content
            ->title('')
            ->description()
            ->body('');
    }

    public function index(Content $content): Content
    {
        return $content
            ->title($this->title)
            ->description($this->description ?? '')
            ->body($this->grid());
    }
}
