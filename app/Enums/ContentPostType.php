<?php

namespace App\Enums;

enum ContentPostType: string
{
    case Blog = 'blog';
    case News = 'news';
    case Event = 'event';
}
