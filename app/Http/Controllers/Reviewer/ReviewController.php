<?php

namespace App\Http\Controllers\Reviewer;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ManuscriptFile;
use App\Models\Review;
use App\Models\ReviewAssignment;
use App\Models\ReviewRound;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $assignments = ReviewAssignment::query()->where('reviewer_id', $request->user()->id)
            ->whereNotIn('status', ['declined', 'cancelled'])
            ->with(['round.manuscript.journal', 'review'])
            ->orderByRaw("CASE status WHEN 'invited' THEN 0 WHEN 'accepted' THEN 1 WHEN 'in_progress' THEN 2 ELSE 3 END")
            ->orderByDesc('created_at')->paginate(20);

        return view('reviewer.dashboard', compact('assignments'));
    }

    public function show(Request $request, ReviewAssignment $assignment): View
    {
        $assignment = ReviewAssignment::query()->whereKey($assignment->id)->where('reviewer_id', $request->user()->id)
            ->with(['round.manuscript.journal', 'round.manuscript.files', 'review'])->firstOrFail();

        return view('reviewer.assignments.show', compact('assignment'));
    }

    public function accept(Request $request, ReviewAssignment $assignment): RedirectResponse
    {
        $locked = null;
        DB::transaction(function () use ($request, $assignment, &$locked): void {
            $locked = ReviewAssignment::query()->whereKey($assignment->id)->where('reviewer_id', $request->user()->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'invited', 422, 'This invitation is no longer awaiting a response.');
            $locked->update(['status' => 'accepted', 'accepted_at' => now()]);
            $this->audit($request, 'reviewer.invitation_accepted', $locked);
        });

        return redirect()->route('reviewer.assignments.show', $locked)->with('status', 'Review invitation accepted. The manuscript files and review form are now available.');
    }

    public function decline(Request $request, ReviewAssignment $assignment): RedirectResponse
    {
        DB::transaction(function () use ($request, $assignment): void {
            $locked = ReviewAssignment::query()->whereKey($assignment->id)->where('reviewer_id', $request->user()->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'invited', 422, 'This invitation is no longer awaiting a response.');
            $locked->update(['status' => 'declined', 'completed_at' => now()]);
            $this->audit($request, 'reviewer.invitation_declined', $locked);
            $this->closeRoundIfFinished($locked->review_round_id);
        });

        return redirect()->route('reviewer.dashboard')->with('status', 'The invitation was declined. The editorial team can assign another reviewer.');
    }

    public function file(Request $request, ReviewAssignment $assignment, ManuscriptFile $file)
    {
        $assignment = ReviewAssignment::query()->whereKey($assignment->id)->where('reviewer_id', $request->user()->id)->with('round')->firstOrFail();
        abort_unless(in_array($assignment->status, ['accepted', 'in_progress'], true), 404);
        abort_unless($file->manuscript_id === $assignment->round->manuscript_id && $file->disk === 'manuscripts', 404);
        $disk = Storage::disk($file->disk);
        abort_unless($disk->exists($file->path), 404);
        $stream = $disk->readStream($file->path);
        abort_unless(is_resource($stream), 404);
        $name = rawurlencode(basename(str_replace('\\', '/', $file->original_name)));

        return response()->stream(function () use ($stream): void {
            fpassthru($stream);
            if (is_resource($stream)) fclose($stream);
        }, 200, [
            'Content-Type' => $file->mime_type,
            'Content-Disposition' => "attachment; filename=\"review-file\"; filename*=UTF-8''{$name}",
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function submit(Request $request, ReviewAssignment $assignment): RedirectResponse
    {
        $data = $request->validate([
            'recommendation' => ['required', 'in:accept,minor_revision,major_revision,reject'],
            'comments_to_author' => ['required', 'string', 'min:20', 'max:20000'],
            'confidential_comments_to_editor' => ['nullable', 'string', 'max:20000'],
            'ratings' => ['nullable', 'array'],
            'ratings.*' => ['integer', 'min:1', 'max:5'],
        ]);

        DB::transaction(function () use ($request, $assignment, $data): void {
            $locked = ReviewAssignment::query()->whereKey($assignment->id)->where('reviewer_id', $request->user()->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($locked->status, ['accepted', 'in_progress'], true), 404);
            Review::query()->updateOrCreate(
                ['review_assignment_id' => $locked->id],
                [
                    'recommendation' => $data['recommendation'],
                    'comments_to_author' => $data['comments_to_author'],
                    'confidential_comments_to_editor' => $data['confidential_comments_to_editor'] ?? null,
                    'ratings' => $data['ratings'] ?? [],
                    'submitted_at' => now(),
                ],
            );
            $locked->update(['status' => 'completed', 'completed_at' => now(), 'accepted_at' => $locked->accepted_at ?? now()]);
            $this->audit($request, 'review.submitted', $locked);
            $this->closeRoundIfFinished($locked->review_round_id);
        });

        return redirect()->route('reviewer.assignments.show', $assignment)->with('status', 'Your review was submitted confidentially to the editorial team.');
    }

    private function closeRoundIfFinished(int $roundId): void
    {
        $hasOutstanding = ReviewAssignment::query()->where('review_round_id', $roundId)
            ->whereIn('status', ['invited', 'accepted', 'in_progress'])->exists();
        if (!$hasOutstanding) {
            ReviewRound::query()->whereKey($roundId)->where('status', 'open')->update(['status' => 'closed', 'closed_at' => now(), 'updated_at' => now()]);
        }
    }

    private function audit(Request $request, string $action, ReviewAssignment $assignment): void
    {
        AuditLog::query()->create([
            'actor_id' => $request->user()->id,
            'action' => $action,
            'subject_type' => ReviewAssignment::class,
            'subject_id' => $assignment->id,
            'metadata' => ['manuscript_id' => $assignment->round->manuscript_id],
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500),
            'created_at' => now(),
        ]);
    }
}
