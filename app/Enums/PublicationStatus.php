<?php

namespace App\Enums;

enum PublicationStatus: string
{
    case Draft = 'draft';
    case Saved = 'saved';
    case Publishing = 'publishing';
    case Published = 'published';
    case Failed = 'publish_failed';
}
