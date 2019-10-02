<?php
/**
 * OAuth 2.0 Client plugin for Craft CMS 3.x
 *
 * Simple OAuth 2.0 client
 *
 * @link      https://venveo.com
 * @copyright Copyright (c) 2019 Venveo
 */

namespace venveo\oauthclient\providers;

use League\OAuth2\Client\Provider\Google as GoogleProvider;
use venveo\oauthclient\base\Provider;

/**
 * @author    Venveo
 * @package   OauthClient
 * @since     1.0.0
 */
class Google extends Provider
{
    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return 'Google';
    }

    /**
     * @inheritDoc
     */
    public static function getProviderClass(): string
    {
        return GoogleProvider::class;
    }
}
