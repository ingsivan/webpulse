<?php

namespace App\Enums;

enum IssueCategory: string
{
    case Performance = 'performance';
    case Seo = 'seo';
    case Links = 'links';

    public function label(): string
    {
        return match ($this) {
            self::Performance => 'Rendimiento',
            self::Seo => 'SEO',
            self::Links => 'Enlaces e imágenes',
        };
    }
}
