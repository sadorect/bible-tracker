namespace App\View\Components;

use Illuminate\View\Component;

class PlatoonDashboard extends Component
{
    public function render()
    {
        $squads = auth()->user()->platoon->squads()->with('leader')->get();
        $progressStats = $this->calculateProgressStats();
        
        return view('components.dashboards.platoon', compact('squads', 'progressStats'));
    }
}
