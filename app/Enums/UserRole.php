<?php

namespace App\Enums;

enum UserRole: string
{
    case Customer = 'customer';
    case Kitchen = 'kitchen';
    case Admin = 'admin';
}
