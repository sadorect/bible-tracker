<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\ReadingProgress;
use App\Models\BibleChapter;
use Carbon\Carbon;

class ReadingTracker extends Component
{
    public $testament;
    public $currentDay;
    public $totalDays;
    public $completionRate;
    public $todayChapters;

    protected $listeners = ['refreshProgress'];

    public function mount($testament)
    {
        $this->testament = $testament;
        $this->refreshProgress();
    }

    public function markAsComplete()
    {
        ReadingProgress::create([
            'user_id' => auth()->id(),
            'day_number' => $this->currentDay,
            'testament' => $this->testament,
            'chapters_range' => BibleChapter::getDayRange($this->currentDay, $this->testament),
            'is_completed' => true,
            'completed_at' => Carbon::now()
        ]);

        $this->refreshProgress();
        $this->emit('readingCompleted');
    }

    public function refreshProgress()
    {
        $userId = auth()->id();
        $this->currentDay = ReadingProgress::getNextDay($userId, $this->testament);
        $this->totalDays = BibleChapter::getTotalDays($this->testament);
        $this->completionRate = $this->calculateCompletionRate($userId);
        $this->todayChapters = BibleChapter::getChaptersForDay($this->currentDay, $this->testament);
    }

    private function calculateCompletionRate($userId)
    {
        $completed = ReadingProgress::getCurrentProgress($userId, $this->testament);
        return ($completed / $this->totalDays) * 100;
    }

    public function render()
    {
        return view('livewire.reading-tracker');
    }
}
