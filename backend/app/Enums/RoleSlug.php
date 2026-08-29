<?php

namespace App\Enums;

enum RoleSlug: string
{
    case CUSTOMER = 'customer';
    case SHOPPER = 'shopper';
    case ADMIN = 'admin';
}
