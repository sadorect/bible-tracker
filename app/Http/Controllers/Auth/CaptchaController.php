<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CaptchaController extends Controller
{
    /**
     * Refresh simple math captcha operands and store in session according to context.
     */
    public function refresh(Request $request)
    {
        $context = $request->query('for');
        $a = random_int(1, 9);
        $b = random_int(1, 9);
        $sum = $a + $b;

        switch ($context) {
            case 'login':
                session(['captcha_login_a' => $a, 'captcha_login_b' => $b, 'captcha_login_sum' => $sum]);
                break;
            case 'register':
                session(['captcha_register_a' => $a, 'captcha_register_b' => $b, 'captcha_register_sum' => $sum]);
                break;
            case 'phone':
                session(['captcha_phone_a' => $a, 'captcha_phone_b' => $b, 'captcha_phone_sum' => $sum]);
                break;
            case 'password-request':
                session(['captcha_pwreq_a' => $a, 'captcha_pwreq_b' => $b, 'captcha_pwreq_sum' => $sum]);
                break;
            case 'password-reset':
                session(['captcha_pwreset_a' => $a, 'captcha_pwreset_b' => $b, 'captcha_pwreset_sum' => $sum]);
                break;
            default:
                // Unknown context; still return fresh operands without storing
                break;
        }

        return response()->json(['a' => $a, 'b' => $b]);
    }
}
