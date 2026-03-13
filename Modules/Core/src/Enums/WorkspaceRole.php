<?php

namespace Modules\Core\Enums;

enum WorkspaceRole: string
{
    case OWNER  = 'owner';
    case ADMIN  = 'admin';
    case MEMBER = 'member';
    case VIEWER = 'viewer';
}
