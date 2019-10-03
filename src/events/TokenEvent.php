<?php
/**
 *  OAuth 2.0 Client plugin for Craft CMS 3
 *  @link      https://www.venveo.com
 *  @copyright Copyright (c) 2018-2019 Venveo
 */

namespace venveo\oauthclient\events;

use venveo\oauthclient\models\App;
use venveo\oauthclient\models\Token;
use yii\base\Event;

class TokenEvent extends Event
{
    public function __construct(Token $token)
    {
        parent::__construct();
        $this->token = $token;
    }

    /** @var Token */
    public $token;
}