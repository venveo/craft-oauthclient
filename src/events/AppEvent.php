<?php

namespace venveo\oauthclient\events;

use venveo\oauthclient\models\App;
use yii\base\Event;

class AppEvent extends Event
{
    /** @var App */
    public $app;
}