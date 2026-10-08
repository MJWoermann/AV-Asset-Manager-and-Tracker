<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditableObserver
{
    public function created(Model $model): void
    {
        $this->log($model, 'created', null, $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $changes = $model->getChanges();
        unset($changes['updated_at']);

        if ($changes === []) {
            return;
        }

        $old = [];
        foreach (array_keys($changes) as $key) {
            $old[$key] = $model->getOriginal($key);
        }

        $this->log($model, 'updated', $old, $changes);
    }

    public function deleted(Model $model): void
    {
        $this->log($model, 'deleted', $model->getAttributes(), null);
    }

    protected function log(Model $model, string $action, ?array $old, ?array $new): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => $model::class,
            'auditable_id' => $model->getKey(),
            'action' => $action,
            'old_values' => $this->sanitize($old),
            'new_values' => $this->sanitize($new),
            'created_at' => now(),
        ]);
    }

    protected function sanitize(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        unset($values['password'], $values['remember_token']);

        return $values;
    }
}
