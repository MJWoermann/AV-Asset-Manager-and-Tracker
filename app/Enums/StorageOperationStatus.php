<?php

namespace App\Enums;

enum StorageOperationStatus: string
{
    case Queued = 'queued';
    case Running = 'running';
    case Done = 'done';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Queued => 'Queued',
            self::Running => 'Running',
            self::Done => 'Done',
            self::Failed => 'Failed',
        };
    }
}
