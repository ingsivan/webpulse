<?php

namespace App\Enums;

enum IssueCategory: string
{
    case Performance = 'performance';
    case Seo = 'seo';
    case Links = 'links';
}
