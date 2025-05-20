namespace App\View\Components;

use Illuminate\View\Component;

class TeamDashboard extends Component
{
    public function render()
    {
        $members = auth()->user()->team->members()
            ->with('readingProgress')
            ->get();
            
        $teamStats = $this->getTeamStatistics();
        $currentDay = $this->getCurrentReadingDay();
        
        return view('components.dashboards.team', compact('members', 'teamStats', 'currentDay'));
    }
}
