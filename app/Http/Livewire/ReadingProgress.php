namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\ReadingProgress;
use Carbon\Carbon;

class ReadingProgress extends Component
{
    public $dayNumber;
    public $isCompleted = false;
    
    public function mount()
    {
        $this->dayNumber = $this->getCurrentDay();
        $this->isCompleted = $this->checkIfCompleted();
    }
    
    public function markAsComplete()
    {
        $progress = ReadingProgress::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'day_number' => $this->dayNumber,
            ],
            [
                'is_completed' => true,
                'completed_at' => Carbon::now(),
            ]
        );
        
        $this->isCompleted = true;
        $this->emit('progressUpdated');
    }
    
    public function toggleDay($dayNumber)
    {
        $progress = ReadingProgress::where('user_id', auth()->id())
            ->where('day_number', $dayNumber)
            ->first();
            
        if ($progress) {
            $progress->update([
                'is_completed' => !$progress->is_completed,
                'completed_at' => !$progress->is_completed ? Carbon::now() : null
            ]);
        }
        
        $this->emit('progressUpdated');
    }
    
    public function render()
    {
        return view('livewire.reading-progress', [
            'progress' => auth()->user()->readingProgress()->orderBy('day_number')->get()
        ]);
    }
}
