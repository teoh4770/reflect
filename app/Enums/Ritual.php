<?php

namespace App\Enums;

enum Ritual: string
{
	case PAIN = 'pain';
    case ANTI_VISION = 'anti-vision';
    case VISION = 'vision';
    case INTERRUPT = 'interrupt';
}
