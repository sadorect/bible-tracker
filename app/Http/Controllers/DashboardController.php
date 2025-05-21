<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Hierarchy;
use App\Models\BibleChapter;
use Illuminate\Http\Request;
use App\Models\ReadingProgress;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        $newTestamentProgress = [
            'current_day' => ReadingProgress::getNextDay($user->id, 'new'),
            'total_days' => BibleChapter::getTotalDays('new'),
            'completion_rate' => $this->calculateCompletionRate($user->id, 'new'),
            'today_chapters' => $this->getTodayChapters($user->id, 'new')
        ];
        
        $oldTestamentProgress = [
            'current_day' => ReadingProgress::getNextDay($user->id, 'old'),
            'total_days' => BibleChapter::getTotalDays('old'),
            'completion_rate' => $this->calculateCompletionRate($user->id, 'old'),
            'today_chapters' => $this->getTodayChapters($user->id, 'old')
        ];

        $hierarchyData = $this->getHierarchyData($user);

        return view('dashboard-content', compact(
            'newTestamentProgress', 
            'oldTestamentProgress',
            'hierarchyData'
        ));
    }

    private function calculateCompletionRate($userId, $testament)
    {
        $completed = ReadingProgress::getCurrentProgress($userId, $testament);
        $total = BibleChapter::getTotalDays($testament);
        return ($completed / $total) * 100;
    }

    private function getTodayChapters($userId, $testament)
    {
        $nextDay = ReadingProgress::getNextDay($userId, $testament);
        return BibleChapter::getDayRange($nextDay, $testament);
    }


    protected function getHierarchyData($user)
    {
        $data = [];

        if ($user->role === 'platoon_leader') {
            $data['squads'] = Hierarchy::where('parent_id', $user->platoon->id)
                ->with(['leader', 'members', 'batches.teams.leader', 'batches.teams.members'])
                ->get();
        } elseif ($user->role === 'squad_leader') {
            $data['batches'] = Hierarchy::where('parent_id', $user->squad->id)
                ->with(['leader', 'members', 'teams.leader', 'teams.members'])
                ->get();
        } elseif ($user->role === 'batch_leader') {
            $data['teams'] = Hierarchy::where('parent_id', $user->batch->id)
                ->with(['leader', 'members'])
                ->get();
        }

        return $data;
    }

    protected function getPlatoonData($user)
    {
        return [
            'squads' => $user->platoon->squads()->with(['leader', 'batches'])->get(),
            'totalMembers' => $user->platoon->getAllMembers()->count(),
            'activeToday' => $user->platoon->getActiveMembersToday()->count()
        ];
    }

    protected function getSquadData($user)
    {
        return [
            'batches' => $user->squad->batches()->with(['leader', 'teams'])->get(),
            'totalMembers' => $user->squad->getAllMembers()->count(),
            'activeToday' => $user->squad->getActiveMembersToday()->count()
        ];
    }

    protected function getBatchData($user)
    {
        return [
            'teams' => $user->batch->teams()->with(['leader', 'members'])->get(),
            'totalMembers' => $user->batch->getAllMembers()->count(),
            'activeToday' => $user->batch->getActiveMembersToday()->count()
        ];
    }

    protected function getTeamData($user)
    {
        return [
            'members' => $user->team->members()->with('readingProgress')->get(),
            'totalMembers' => $user->team->members()->count(),
            'activeToday' => $user->team->getActiveMembersToday()->count()
        ];
    }

    protected function getMemberData($user)
    {
        return [
            'progress' => $user->readingProgress()->orderBy('day_number')->get(),
            'completedToday' => $user->hasCompletedToday(),
            'totalProgress' => $user->getProgressPercentage()
        ];
    }

    public function manageHierarchy()
    {
        $user = auth()->user();
        $hierarchyData = $this->getHierarchyData($user);

        return view('admin.hierarchy.manage-hierarchy', compact('hierarchyData'));
    }
}
