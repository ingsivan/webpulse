<?php

namespace App\Enums;

enum AuditStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En cola',
            self::Running => 'Analizando',
            self::Completed => 'Completada',
            self::Failed => 'Fallida',
        };
    }
}
