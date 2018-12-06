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

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Google as GoogleProvider;
use venveo\oauthclient\base\Provider;
use venveo\oauthclient\models\Token;

/**
 * @author    Venveo
 * @package   OauthClient
 * @since     1.0.0
 */
class Google extends Provider
{
    public static function getDisplayName(): string
    {
        return 'Google';
    }

    public function getConfiguredProvider(): GoogleProvider
    {
        if ($this->configuredProvider instanceof AbstractProvider) {
            return $this->configuredProvider;
        }

        $this->configuredProvider = new GoogleProvider([
            'clientId' => $this->getApp()->getClientId(),
            'clientSecret' => $this->getApp()->getClientSecret(),
            'redirectUri' => $this->getApp()->getRedirectUrl(),
            'accessType' => 'offline',
        ]);
        return $this->configuredProvider;
    }

    /**
     * @param array $options
     * @return String
     */
    public function getAuthorizeURL($options = []): String {
        return $this->getConfiguredProvider()->getAuthorizationUrl(
            array_merge([
                'scope' => $this->getApp()->getScopes(),
                'approval_prompt' => 'force'
            ],
            $options)
        );
    }
}
