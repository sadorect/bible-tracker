namespace App\View\Components;

use Illuminate\View\Component;

class ClanDashboard extends Component
{
    public function render()
    {
        $platoons = auth()->user()->clan->platoons;
        return view('components.dashboards.clan', compact('platoons'));
    }
}
