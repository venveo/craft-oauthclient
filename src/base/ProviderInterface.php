<?php

namespace venveo\oauthclient\base;

use League\OAuth2\Client\Provider\AbstractProvider;
use venveo\oauthclient\models\Token as TokenModel;

/**
 * The ProviderInterface dictates the required properties for a provider to function in the Craft environment
 * Interface ProviderInterface
 * @package venveo\oauthclient\base
 */
interface ProviderInterface
{
    /**
     * A friendly name for this provider
     * @return string
     */
    public static function displayName(): string;

    /**
     * Gets a concrete League provider instance
     * @return AbstractProvider
     */
    public function getConfiguredProvider();

    /**
     * Get the URL used to authorize the token
     * @param $options
     * @return string
     */
    public function getAuthorizeURL($options): string;

    /**
     * Get the session security state
     * @return string|null
     */
    public function getState(): ?string;

    /**
     * Gets an access token, returing a League access token
     * @param $grant
     * @param array $options
     * @return \League\OAuth2\Client\Token\AccessTokenInterface
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     */
    public function getAccessToken($grant, $options = []);

    /**
     * Refresh an access token
     * @param TokenModel $tokenModel
     * @param array $options
     * @return TokenModel
     */
    public function refreshToken(TokenModel $tokenModel, $options = []): TokenModel;
}
