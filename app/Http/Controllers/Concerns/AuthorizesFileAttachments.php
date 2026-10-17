<?php

namespace App\Http\Controllers\Concerns;

use App\Models\File;
use App\Models\User;
use App\Services\FileAttachmentService;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared file auth helpers for web and API file controllers.
 *
 * @property FileAttachmentService $attachments
 */
trait AuthorizesFileAttachments
{
    protected function linkableFromRequest(?string $type, mixed $id): ?Model
    {
        if ($type === null || $id === null || $id === '') {
            return null;
        }

        return $this->attachments->resolveLinkable($type, (int) $id);
    }

    protected function authorizeMutate(?Model $linkable): void
    {
        if ($linkable === null) {
            $this->authorizeManager();

            return;
        }

        $type = $this->attachments->linkableTypeKey($linkable);

        if (in_array($type, ['location', 'item_type'], true)) {
            $this->authorizeManager();
        }
    }

    protected function authorizeView(File $file): void
    {
        /** @var User|null $user */
        $user = auth()->user();

        if ($user?->hasAnyRole(['admin', 'inventory_manager'])) {
            return;
        }

        abort_unless($user !== null && $file->links()->exists(), 403);
    }

    protected function authorizeManager(): void
    {
        /** @var User|null $user */
        $user = auth()->user();
        abort_unless($user?->hasAnyRole(['admin', 'inventory_manager']), 403);
    }
}
