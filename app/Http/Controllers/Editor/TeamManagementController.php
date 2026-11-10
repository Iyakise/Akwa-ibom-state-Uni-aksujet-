<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TeamManagementController extends Controller
{
    public function index(): View
    {
        $users = User::query()->with('roles')->orderBy('name')->paginate(20);

        return view('editor.team.index', compact('users'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless(!$user->isEditorialAllowlisted() && !$user->hasRole('administrator'), 422, 'The allowlisted administrator account is protected from role changes here.');
        $data = $request->validate([
            'role' => ['required', Rule::in(['author', 'reviewer'])],
        ]);
        abort_unless($user->email_verified_at !== null && $user->account_status === 'active', 422, 'Only active, verified accounts can be assigned an editorial workflow role.');
        $role = Role::query()->where('name', $data['role'])->firstOrFail();

        DB::transaction(function () use ($request, $user, $role): void {
            $user->roles()->sync([$role->id => ['granted_by' => $request->user()->id, 'granted_at' => now()]]);
            AuditLog::query()->create([
                'actor_id' => $request->user()->id,
                'action' => 'account.role_assigned',
                'subject_type' => User::class,
                'subject_id' => $user->id,
                'metadata' => ['role' => $role->name],
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 500),
                'created_at' => now(),
            ]);
        });

        return back()->with('status', 'Account role updated.');
    }
}
