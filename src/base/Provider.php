<?php

namespace venveo\oauthclient\base;

use craft\base\Component;
use League\OAuth2\Client\Grant\RefreshToken;
use League\OAuth2\Client\Provider\AbstractProvider;
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

    /**
     * Sets the app model
     * @param AppModel $app
     */
    public function setApp(AppModel $app)
    {
        $this->app = $app;
    }

    /**
     * Gets the app this concrete provider is configured for.
     * @return AppModel
     */
    public function getApp(): AppModel
    {
        return $this->app;
    }

    /**
     * Provides the options that will be passed to the new instance of the League provider.
     * If you override this, make sure you merge in the parent options (these).
     * @return array
     */
    public function getProviderOptions(): array
    {
        return [
            'clientId' => $this->getApp()->getClientId(),
            'clientSecret' => $this->getApp()->getClientSecret(),
            'redirectUri' => $this->getApp()->getRedirectUrl()
        ];
    }

    /**
     * Provide additional options for the authorization URL.
     * @return array
     */
    public function getDefaultAuthorizationUrlOptions(): array
    {
        return [
            'scope' => $this->getApp()->getScopes()
        ];
    }

    /**
     * @inheritDoc
     */
    public function getAuthorizeURL($options = []): string
    {
        $options = array_merge($this->getDefaultAuthorizationUrlOptions(), $options);
        $provider = $this->getConfiguredProvider();
        return $provider->getAuthorizationUrl($options);
    }

    /**
     * @inheritDoc
     * @throws \ReflectionException
     */
    public function getConfiguredProvider()
    {
        if ($this->configuredProvider instanceof AbstractProvider) {
            return $this->configuredProvider;
        }

        $leagueProvider = new \ReflectionClass(static::getProviderClass());

        $this->configuredProvider = $leagueProvider->newInstance($this->getProviderOptions());

        return $this->configuredProvider;
    }

    /**
     * Gets a unique state parameter. We're gonna use the CSRF token by default
     * @return string|null
     */
    public function getState(): ?string
    {
        return \Craft::$app->request->getCsrfToken();
    }

    /**
     * @inheritDoc
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
    public function refreshToken(TokenModel $tokenModel, $options = []): TokenModel
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

    /**
     * Get the class name for the League provider
     * @return string
     */
    abstract public static function getProviderClass(): string;

    public function __toString()
    {
        return static::displayName();
    }
}
