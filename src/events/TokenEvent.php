<?php
/**
 *  OAuth 2.0 Client plugin for Craft CMS 3
 * @link      https://www.venveo.com
 * @copyright Copyright (c) 2018-2019 Venveo
 */

namespace venveo\oauthclient\events;

use craft\events\ModelEvent;
use League\OAuth2\Client\Token\AccessTokenInterface;
use venveo\oauthclient\models\Token;

class TokenEvent extends ModelEvent
{
    /** @var AccessTokenInterface */
    public $responseToken;

    /** @var Token */
    public $token;
}