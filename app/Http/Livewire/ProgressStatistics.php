namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\ReadingProgress;
use App\Events\ReadingCompleted;

class ProgressStatistics extends Component
{
    protected $listeners = ['progressUpdated' => '$refresh'];

    public function getStats()
    {
        $user = auth()->user();
        $totalDays = 365; // Or your total reading days
        
        return [
            'completedDays' => $user->readingProgress()->where('is_completed', true)->count(),
            'currentStreak' => $this->calculateStreak(),
            'totalProgress' => ($user->readingProgress()->where('is_completed', true)->count() / $totalDays) * 100,
            'weeklyProgress' => $this->getWeeklyProgress()
        ];
    }

    public function calculateStreak()
    {
        $streak = 0;
        $progress = auth()->user()
            ->readingProgress()
            ->where('is_completed', true)
            ->orderBy('day_number', 'desc')
            ->get();

        foreach ($progress as $day) {
            if ($day->completed_at->isYesterday() || $day->completed_at->isToday()) {
                $streak++;
            } else {
                break;
            }
        }

        return $streak;
    }

    public function getWeeklyProgress()
    {
        return auth()->user()
            ->readingProgress()
            ->where('completed_at', '>=', now()->subDays(7))
            ->where('is_completed', true)
            ->count();
    }

    public function render()
    {
        return view('livewire.progress-statistics', [
            'stats' => $this->getStats()
        ]);
    }
}
