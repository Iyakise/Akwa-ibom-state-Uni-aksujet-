<?php

namespace App\Services;

use App\Enums\ManuscriptStatus;
use App\Models\AuditLog;
use App\Models\Manuscript;
use App\Models\ManuscriptStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManuscriptWorkflowService
{
    public function transition(Manuscript $manuscript, ManuscriptStatus $to, User $actor, ?string $note = null): Manuscript
    {
        return DB::transaction(function () use ($manuscript, $to, $actor, $note): Manuscript {
            $locked = Manuscript::query()->lockForUpdate()->findOrFail($manuscript->id);
            $from = ManuscriptStatus::tryFrom($locked->status);

            if (!$from || !in_array($to, $from->next(), true)) {
                throw ValidationException::withMessages(['status' => 'That manuscript status transition is not allowed.']);
            }

            $locked->status = $to->value;
            if ($to->isTerminal()) {
                $locked->closed_at = now();
            } elseif ($locked->closed_at) {
                $locked->closed_at = null;
            }
            $locked->save();

            ManuscriptStatusHistory::query()->create([
                'manuscript_id' => $locked->id,
                'changed_by' => $actor->id,
                'from_status' => $from->value,
                'to_status' => $to->value,
                'note' => $note,
                'created_at' => now(),
            ]);

            AuditLog::query()->create([
                'actor_id' => $actor->id,
                'action' => 'manuscript.status_changed',
                'subject_type' => Manuscript::class,
                'subject_id' => $locked->id,
                'metadata' => ['from' => $from->value, 'to' => $to->value],
                'created_at' => now(),
            ]);

            return $locked->refresh();
        });
    }
}
