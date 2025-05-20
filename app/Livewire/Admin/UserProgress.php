<?php

namespace App\Livewire\Admin;

use App\Models\ReadingPlan;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class UserProgress extends Component
{
    use WithPagination;
    
    public $selectedPlan = null;
    public $readingPlans = [];
    public $searchTerm = '';
    
    public function mount()
    {
        $this->readingPlans = ReadingPlan::all();
        if ($this->readingPlans->count() > 0) {
            $this->selectedPlan = $this->readingPlans->first()->id;
        }
    }
    
    public function render()
    {
        $users = User::query()
            ->whereHas('readingPlans', function ($query) {
                $query->where('reading_plan_id', $this->selectedPlan);
            })
            ->when($this->searchTerm, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->searchTerm . '%')
                      ->orWhere('email', 'like', '%' . $this->searchTerm . '%');
                });
            })
            ->with(['readingPlans' => function ($query) {
                $query->where('reading_plan_id', $this->selectedPlan);
            }])
            ->paginate(10);
        
        return view('livewire.admin.user-progress', [
            'users' => $users,
            'selectedPlanDetails' => ReadingPlan::find($this->selectedPlan),
        ]);
    }
    
    public function sendReminderToAll()
    {
        $plan = ReadingPlan::find($this->selectedPlan);
        $users = User::whereHas('readingPlans', function ($query) {
            $query->where('reading_plan_id', $this->selectedPlan);
        })->get();
        
        // In a real app, you would send notifications here
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Reminders sent to ' . $users->count() . ' users',
        ]);
    }
    
    public function sendReminderToUser($userId)
    {
        $user = User::find($userId);
        
        // In a real app, you would send a notification here
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Reminder sent to ' . $user->name,
        ]);
    }
}