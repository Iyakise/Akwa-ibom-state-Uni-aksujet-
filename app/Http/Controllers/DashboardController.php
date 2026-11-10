<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasAnyRole(['administrator', 'editor'])) {
            return redirect()->route('editor.dashboard');
        }
        if ($user->hasRole('reviewer')) {
            return redirect()->route('reviewer.dashboard');
        }
        if ($user->hasRole('author')) {
            return redirect()->route('author.submissions.index');
        }

        abort(403, 'Your account has no active Aksujet role. Contact the journal administrator.');
    }
}
