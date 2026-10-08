<?php

namespace App\Support;

use App\User;

class CartonReverseAuthorization
{
    public const ALLOWED_EMAIL = 'aaura1177@gmail.com';

    public static function canReverse(?User $user): bool
    {
        if (!$user || empty($user->email)) {
            return false;
        }

        return strcasecmp(trim($user->email), self::ALLOWED_EMAIL) === 0;
    }
}
