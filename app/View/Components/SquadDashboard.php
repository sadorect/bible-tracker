namespace App\View\Components;

use Illuminate\View\Component;

class SquadDashboard extends Component
{
    public function render()
    {
        $batches = auth()->user()->squad->batches()
            ->with(['leader', 'teams.leader', 'teams.members'])
            ->get();
            
        $squadStats = $this->getSquadStatistics();
        
        return view('components.dashboards.squad', compact('batches', 'squadStats'));
    }
}
