namespace App\View\Components;

use Illuminate\View\Component;

class MemberDashboard extends Component
{
    public function render()
    {
        $user = auth()->user();
        $readingProgress = $user->readingProgress()
            ->orderBy('day_number')
            ->get();
        $currentDay = $this->getCurrentReadingDay();
        $stats = $this->getMemberStatistics();
        
        return view('components.dashboards.member', compact('readingProgress', 'currentDay', 'stats'));
    }
}
