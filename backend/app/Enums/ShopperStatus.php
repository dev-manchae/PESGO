<?php

namespace App\Enums;

enum ShopperStatus: string
{
    case NONE = 'none';
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
}
