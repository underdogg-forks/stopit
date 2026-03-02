<?php

namespace Modules\Core\Enums;

enum Severity: string
{
    case INFO     = 'info';
    case WARNING  = 'warning';
    case ERROR    = 'error';
    case CRITICAL = 'critical';
}
