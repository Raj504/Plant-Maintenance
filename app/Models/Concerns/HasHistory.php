<?php

namespace App\Models\Concerns;

use App\Models\History;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Audit trail. Any model using this trait can call
 *   $model->logHistory('approved', 'Budget approved', 'approval_pending', 'approved');
 * and read it back with $model->history.
 */
trait HasHistory
{
    public function history(): MorphMany
    {
        return $this->morphMany(History::class, 'model')->latest('id');
    }

    public function logHistory(string $action, ?string $remarks = null, ?string $from = null, ?string $to = null, ?array $changes = null): History
    {
        return $this->history()->create([
            'user_id'     => auth()->id(),
            'action'      => $action,
            'from_status' => $from,
            'to_status'   => $to,
            'remarks'     => $remarks,
            'changes'     => $changes,
        ]);
    }
}
