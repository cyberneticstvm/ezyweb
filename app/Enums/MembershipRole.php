<?php

namespace App\Enums;

enum MembershipRole: string
{
    case Owner = 'tenant_owner';
    case Admin = 'tenant_admin';
    case WebsiteEditor = 'website_editor';
    case ContentEditor = 'content_editor';
    case Viewer = 'viewer';
}
