namespace App\View\Components;

use Illuminate\View\Component;

class BatchDashboard extends Component
{
    public function render()
    {
        $teams = auth()->user()->batch->teams()
            ->with(['leader', 'members', 'members.readingProgress'])
            ->get();
            
        $batchStats = $this->getBatchStatistics();
        
        return view('components.dashboards.batch', compact('teams', 'batchStats'));
    }
}
