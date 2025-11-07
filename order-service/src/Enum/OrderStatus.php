<?php

declare(strict_types=1);

namespace App\Enum;

enum OrderStatus: string
{
    case PROCESSING = 'Processing';
    case COMPLETED = 'Completed';
    case CANCELLED = 'Cancelled';
}
