<?php
/**
 *  OAuth 2.0 Client plugin for Craft CMS 3
 * @link      https://www.venveo.com
 * @copyright Copyright (c) 2020 Venveo
 */

namespace venveo\oauthclient\events;

use venveo\oauthclient\models\Token;
use yii\base\Event;

class AuthorizationEvent extends Event
{
    /**
     * The context of the request
     *
     * @var string
     */
    public $context;

    /**
     * The URL the user will be sent to after successfully authenticating
     *
     * @var string
     */
    public $returnUrl;

    /**
     * The handle for the app we're authorizing with
     *
     * @var string
     */
    public $appHandle;

    /**
     * If the authorization was successful, this will be set to the created TokenModel
     *
     * @var Token|null
     */
    public $token;
}