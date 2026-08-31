<?php

namespace App\Enums;

enum CollectionItemStatus: string
{
    case Owned = 'owned';
    case Wishlist = 'wishlist';
    case Reading = 'reading';
    case Read = 'read';
    case Lent = 'lent';
    case Sold = 'sold';
}
