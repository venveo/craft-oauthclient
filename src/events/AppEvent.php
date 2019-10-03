<?php
/**
 *  OAuth 2.0 Client plugin for Craft CMS 3
 * @link      https://www.venveo.com
 * @copyright Copyright (c) 2018-2019 Venveo
 */

namespace venveo\oauthclient\events;

use venveo\oauthclient\models\App;
use yii\base\Event;

class AppEvent extends Event
{
    public function __construct(App $app)
    {
        parent::__construct();
        $this->app = $app;
    }

    /** @var App */
    public $app;
}