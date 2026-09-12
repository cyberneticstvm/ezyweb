<?php

namespace App\Enums;

enum ContentPostStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Scheduled = 'scheduled';
}
