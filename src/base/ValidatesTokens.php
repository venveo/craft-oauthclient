<?php

namespace venveo\oauthclient\base;

use venveo\oauthclient\models\Token;

/**
 * @package venveo\oauthclient\base
 */
interface ValidatesTokens
{
    public static function checkToken(Token $token): bool;
}
