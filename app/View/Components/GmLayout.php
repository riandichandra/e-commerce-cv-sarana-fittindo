<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class GmLayout extends Component
{
    public function render(): View|Closure|string
    {
        return view('layouts.gm.layout');
    }
}
