<?php

namespace App\Enums;

enum CancelledBy: string
{
    case Customer = 'customer';
    case Business = 'business';
}
