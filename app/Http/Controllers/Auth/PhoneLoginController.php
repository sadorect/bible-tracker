namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class PhoneLoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.phone-login');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'phone_number' => 'required|string|exists:users,phone_number',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($validated)) {
            $request->session()->regenerate();
            return redirect()->intended('dashboard');
        }

        return back()->withErrors([
            'phone_number' => 'The provided credentials do not match our records.',
        ]);
    }
}
