<?php

namespace App\Http\Controllers;

use App\Support\AgentDebugLog;
use App\Support\DepartmentVisibility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartmentVisibilityPreferenceController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('admin'), 403);

        $data = $request->validate([
            'visible_department_ids' => ['nullable', 'array'],
            'visible_department_ids.*' => ['integer', Rule::exists('departments', 'id')],
        ]);

        $user = $request->user();
        $ids = DepartmentVisibility::normalizeIds($data['visible_department_ids'] ?? []);
        // #region agent log
        $pivotBefore = $user->departments()->pluck('departments.id')->map(fn ($id) => (int) $id)->values()->all();
        $prefBefore = DepartmentVisibility::normalizeIds(($user->preferences ?? [])[DepartmentVisibility::PREFERENCE_KEY] ?? null);
        // #endregion
        $user->preferences = DepartmentVisibility::mergePreference($user->preferences, $ids);
        $user->save();

        $active = session(DepartmentVisibility::SESSION_KEY);
        if ($ids !== [] && $active !== null && ! in_array((int) $active, $ids, true)) {
            session([DepartmentVisibility::SESSION_KEY => $ids[0]]);
        }

        // #region agent log
        $pivotAfter = $user->departments()->pluck('departments.id')->map(fn ($id) => (int) $id)->values()->all();
        $prefAfter = DepartmentVisibility::normalizeIds(($user->fresh()->preferences ?? [])[DepartmentVisibility::PREFERENCE_KEY] ?? null);
        AgentDebugLog::write('A', 'DepartmentVisibilityPreferenceController.php:update', 'Profile visibility saved', [
            'userId' => $user->id,
            'submittedIds' => $ids,
            'prefBefore' => $prefBefore,
            'prefAfter' => $prefAfter,
            'pivotBefore' => $pivotBefore,
            'pivotAfter' => $pivotAfter,
            'pivotMatchesPref' => $pivotAfter === $prefAfter,
        ]);
        // #endregion

        return back()->with('status', 'Department visibility updated.');
    }
}
