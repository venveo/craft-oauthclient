<?php

namespace venveo\oauthclient\events;

use venveo\oauthclient\models\App;
use venveo\oauthclient\models\Token;
use yii\base\Event;

class TokenEvent extends Event
{
    /** @var Token */
    public $token;
}