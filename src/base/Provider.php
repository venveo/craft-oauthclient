<?php

namespace venveo\oauthclient\base;

use craft\base\Component;
use League\OAuth2\Client\Grant\RefreshToken;
use venveo\oauthclient\models\App as AppModel;
use venveo\oauthclient\models\Token as TokenModel;

/**
 *
 * @property null|string $state
 */
abstract class Provider extends Component implements ProviderInterface
{
    private $app;
    protected $configuredProvider;

    public function setApp(AppModel $app)
    {
        $this->app = $app;
    }

    public function getApp(): AppModel
    {
        return $this->app;
    }

    public function getAuthorizeURL($options = []): string
    {
        $provider = $this->getConfiguredProvider();
        return $provider->getAuthorizationUrl($options);
    }

    public function getState(): ?string
    {
        return \Craft::$app->request->getCsrfToken();
    }

    /**
     * @param $grant
     * @param array $options
     * @return \League\OAuth2\Client\Token\AccessTokenInterface
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     */
    public function getAccessToken($grant, $options = [])
    {
        return $this->getConfiguredProvider()->getAccessToken($grant, $options);
    }

    /**
     * @param TokenModel $tokenModel
     * @param array $options
     * @return TokenModel
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     */
    public function refreshToken(TokenModel $tokenModel, $options = [])
    {
        $grant = new RefreshToken();
        $token = $this->getConfiguredProvider()->getAccessToken($grant, array_merge([
            'refresh_token' => $tokenModel->refreshToken
        ], $options));

        $tokenModel->accessToken = $token->getToken();
        $tokenModel->expiryDate = $token->getExpires();
        if ($token->getRefreshToken()) {
            $tokenModel->refreshToken = $token->getRefreshToken();
        }
        return $tokenModel;
    }

    public function __toString()
    {
        return static::displayName();
    }
}
