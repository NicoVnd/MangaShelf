<?php

namespace App\Enums;

enum CollectionMemberRole: string
{
    case Owner = 'owner';
    case Editor = 'editor';
    case Viewer = 'viewer';
}
