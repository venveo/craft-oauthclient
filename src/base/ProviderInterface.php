<?php

namespace venveo\oauthclient\base;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessTokenInterface;
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
     * Get the class name for the League provider
     * @return string
     */
    public static function getProviderClass(): string;

    /**
     * Gets a concrete League provider instance
     * @return AbstractProvider
     */
    public function getConfiguredProvider(): AbstractProvider;

    /**
     * Get the URL used to authorize the token
     * @param $options
     * @return string
     */
    public function getAuthorizeURL($options): string;

    /**
     * Get the session security state
     * @return string
     */
    public function getState(): string;

    /**
     * Gets an access token, returning a League access token
     * @param $grant
     * @param array $options
     * @return AccessTokenInterface
     * @throws IdentityProviderException
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
