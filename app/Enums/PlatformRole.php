<?php

namespace App\Enums;

enum PlatformRole: string
{
    case User = 'user';
    case SuperAdmin = 'super_admin';
    case Support = 'platform_support';
    case Finance = 'platform_finance';
}
