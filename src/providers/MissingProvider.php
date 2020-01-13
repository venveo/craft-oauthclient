<?php
/**
 * OAuth 2.0 Client plugin for Craft CMS 3.x
 *
 * Simple OAuth 2.0 client
 *
 * @link      https://venveo.com
 * @copyright Copyright (c) 2018 Venveo
 */

namespace venveo\oauthclient\providers;

use venveo\oauthclient\base\Provider;

/**
 * @author    Venveo
 * @package   OauthClient
 * @since     1.0.0
 */
class MissingProvider extends Provider
{
    public static function getDisplayName(): string
    {
        return 'Provider Missing';
    }

    /**
     * @inheritDoc
     */
    public static function getProviderClass(): string
    {
        return '';
    }

    public function getConfiguredProvider()
    {
        return null;
    }
}
