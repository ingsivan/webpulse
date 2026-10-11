<?php

namespace App\Enums;

enum IssueSeverity: string
{
    case Info = 'info';
    case Warning = 'warning';
    case Error = 'error';

    public function label(): string
    {
        return match ($this) {
            self::Info => 'Sugerencia',
            self::Warning => 'Aviso',
            self::Error => 'Error',
        };
    }
}
