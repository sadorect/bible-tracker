<?php

namespace App\View\Components;

use Illuminate\View\Component;

class SidebarLink extends Component
{
    public $route;
    public $icon;
    public $active;
    
    public function __construct($route = '', $icon = 'home')
    {
        $this->route = $route;
        $this->icon = $icon;
        $this->active = request()->routeIs($route);
    }
    
    public function render()
    {
        return view('components.sidebar-link');
    }
}
