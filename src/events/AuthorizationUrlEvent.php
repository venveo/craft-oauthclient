<?php
/**
 *  OAuth 2.0 Client plugin for Craft CMS 3
 * @link      https://www.venveo.com
 * @copyright Copyright (c) 2018-2019 Venveo
 */

namespace venveo\oauthclient\events;

use venveo\oauthclient\models\App;
use yii\base\Event;

class AuthorizationUrlEvent extends Event
{
    /**
     * This App model for which this event is taking place
     *
     * @var App
     */
    public $app;

    /**
     * Options that will be provided to the league provider's authorization URL method
     *
     * @var array
     */
    public $options = [];

    /**
     * This is a value that can be used to determine what in context the OAuth URL is being generated
     *
     * @var string
     */
    public $context = '';

    /**
     * If set, the authorization URL will be forced to this value. Options will be discarded.
     *
     * @var null
     */
    public $url = null;
}