<?php

namespace App\Utils;

use Laravel\Passport\Token;

class RevokeToken
{
    /**
     * Revoke all tokens for a user.
     *
     * @param int $userId
     *
     * @return bool
     */
    public static function revokeAllTokensForUser($userId){
    $tokens = Token::where('user_id', $userId)->get();

    foreach ($tokens as $token) {
        $token->revoke();
    }

    return true;
    }
}
