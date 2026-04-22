<?php

namespace App\Http\Controllers;

use App\Services\Manual\UserManualService;
use Illuminate\Http\Request;

class UserManualController extends Controller
{
    public function __construct(
        private readonly UserManualService $manualService,
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $view = $user->canAccessAdminPanel()
            ? 'admin.manual.index'
            : 'manual.index';

        return view($view, [
            'guides' => $this->manualService->guidesFor($user),
        ]);
    }

    public function show(Request $request, string $guide)
    {
        $user = $request->user();
        $view = $user->canAccessAdminPanel()
            ? 'admin.manual.show'
            : 'manual.show';

        $guideData = $this->manualService->findGuideFor($user, $guide);

        abort_unless($guideData, 404);

        return view($view, [
            'guide' => $guideData,
            'guides' => $this->manualService->guidesFor($user),
        ]);
    }
}
