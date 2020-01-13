<?php

namespace venveo\oauthclient\base;

use Craft;
use craft\base\Component;
use League\OAuth2\Client\Grant\RefreshToken;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessTokenInterface;
use ReflectionClass;
use ReflectionException;
use venveo\oauthclient\events\TokenEvent;
use venveo\oauthclient\models\App as AppModel;
use venveo\oauthclient\models\Token as TokenModel;

/**
 *
 * @property array $providerOptions
 * @property array $defaultAuthorizationUrlOptions
 * @property null|string $state
 */
abstract class Provider extends Component implements ProviderInterface
{
    const EVENT_CREATE_TOKEN_MODEL_FROM_RESPONSE = 'EVENT_CREATE_TOKEN_MODEL_FROM_RESPONSE';

    protected $configuredProvider;
    private $app;

    /**
     * @inheritDoc
     * @throws ReflectionException
     */
    public function getAuthorizeURL($options = []): string
    {
        $options = array_merge($this->getDefaultAuthorizationUrlOptions(), $options);
        $provider = $this->getConfiguredProvider();
        return $provider->getAuthorizationUrl($options);
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
     * @throws ReflectionException
     */
    public function getConfiguredProvider()
    {
        if ($this->configuredProvider instanceof AbstractProvider) {
            return $this->configuredProvider;
        }

        $leagueProvider = new ReflectionClass(static::getProviderClass());

        $this->configuredProvider = $leagueProvider->newInstance($this->getProviderOptions());

        return $this->configuredProvider;
    }

    /**
     * Get the class name for the League provider
     * @return string
     */
    abstract public static function getProviderClass(): string;

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
            'redirectUri' => $this->getApp()->getRedirectUrl(),
            'urlAuthorize' => $this->getApp()->getUrlAuthorize()
        ];
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
     * Sets the app model
     * @param AppModel $app
     */
    public function setApp(AppModel $app)
    {
        $this->app = $app;
    }

    /**
     * Gets a unique state parameter. We're gonna use the CSRF token by default
     * @return string|null
     */
    public function getState(): string
    {
        return Craft::$app->request->getCsrfToken();
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
     * @throws IdentityProviderException
     * @throws ReflectionException
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
     * Converts the League OAuth access token response to a localized Token model
     * @param AccessTokenInterface $token
     * @return TokenModel
     */
    public function createTokenModelFromResponse(AccessTokenInterface $token): TokenModel
    {
        $tokenModel = new TokenModel([
            'accessToken' => $token->getToken(),
            'refreshToken' => $token->getRefreshToken(),
            'expiryDate' => $token->getExpires()
        ]);
        $event = new TokenEvent(['token' => $tokenModel, 'responseToken' => $token]);
        $this->trigger(self::EVENT_CREATE_TOKEN_MODEL_FROM_RESPONSE, $event);
        return $event->token;
    }

    public function __toString()
    {
        return static::displayName();
    }
}
