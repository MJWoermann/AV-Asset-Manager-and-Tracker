<?php

namespace App\Http\Middleware;

/**
 * Plan-facing name for developer feature gating.
 *
 * Prefer the shared alias `developer_feature:{key}` (or `feature:{key}`),
 * both registered to EnsureUserHasFeature.
 */
class EnsureDeveloperFeature extends EnsureUserHasFeature
{
    //
}
