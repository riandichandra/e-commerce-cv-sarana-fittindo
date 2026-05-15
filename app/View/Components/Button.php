<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Button extends Component
{
    public $bgColor;
    public $textColor;
    public $icon;
    public $size;
    public $href;
    /**
     * Create a new component instance.
     */
    public function __construct($bgColor = 'primary', $textColor = 'white', $icon = null, $size = 'full', $href = null)
    {
        $this->bgColor = $bgColor;
        $this->textColor = $textColor;
        $this->icon = $icon;
        $this->size = $size;
        $this->href = $href;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.button');
    }
}
