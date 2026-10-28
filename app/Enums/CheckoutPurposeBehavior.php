<?php

namespace App\Enums;

enum CheckoutPurposeBehavior: string
{
    case OnEvent = 'on_event';
    case Repair = 'repair';
}
