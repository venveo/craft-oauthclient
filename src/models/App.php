<?php

namespace venveo\oauthclient\models;

use Craft;
use craft\base\Model;
use craft\elements\User;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\validators\UniqueValidator;
use craft\web\View;
use Exception;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Markup;
use venveo\oauthclient\base\Provider;
use venveo\oauthclient\Plugin;
use venveo\oauthclient\records\App as AppRecord;
use venveo\oauthclient\records\Token as TokenRecord;
use yii\base\InvalidConfigException;
use yii\db\ActiveQuery;

/**
 * Class App
 *
 * @since 2.0
 * @property ActiveQuery $tokenRecordQuery
 * @property array $allTokens
 * @property string $redirectUrl
 * @property string $cpEditUrl
 * @property string uid
 */
class App extends Model
{
    public ?string $uid = null;
    public ?int $id = null;
    public ?\DateTime $dateCreated = null;
    public ?\DateTime $dateUpdated = null;
    public string $scopes = '';
    public ?string $provider = null;
    public ?string $name = null;
    public ?string $handle = null;
    public ?string $clientId = null;
    public ?string $clientSecret = null;
    public ?string $urlAuthorize = null;

    private ?Provider $providerInstance = null;

    /**
     * Returns the name of this payment method.
     *
     * @return string
     */
    public function __toString()
    {
        return (string)$this->name;
    }

    /**
     * Parse any environment variables and return the client ID
     *
     * @return string
     */
    public function getClientId(): string
    {
        return \craft\helpers\App::parseEnv($this->clientId);
    }

    /**
     * Parse any environment variables and return the client secret
     *
     * @return string
     */
    public function getClientSecret(): string
    {
        return \craft\helpers\App::parseEnv($this->clientSecret);
    }

    /**
     * Returns the custom authorization URL.
     *
     * @return string|null
     */
    public function getUrlAuthorize(): ?string
    {
        return \craft\helpers\App::parseEnv($this->urlAuthorize);
    }

    /**
     * Get the scopes for the app
     *
     * @param bool $forTable If true, we'll format the output for Craft's table field
     * @return array<string>
     */
    public function getScopes(bool $forTable = false): array
    {
        if ($forTable) {
            return array_map(static function ($scope) {
                return ['scope' => $scope];
            }, explode(',', $this->scopes));
        }
        return array_map('trim', explode(',', $this->scopes));
    }

    /**
     * Get the URL to edit the app in the CP
     *
     * @return string
     */
    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('oauthclient/apps/' . $this->handle);
    }

    /**
     * Get the URL callback URL
     *
     * @param string|null $context A context that will be passed to the controller to help tag events for handling.
     * @param null $returnUrl
     * @return string
     * @throws InvalidConfigException
     * @throws \yii\base\Exception
     */
    public function getRedirectUrl(string $context = null, $returnUrl = null): string
    {
        return UrlHelper::cpUrl('oauth/authorize/' . $this->handle, [
            'context' => $context,
            'returnUrl' => isset($returnUrl) ? Craft::$app->security->hashData(UrlHelper::url($returnUrl)) : null
        ]);
    }

    /**
     * Get an instance of the Provider
     *
     * @return Provider|null
     * @throws InvalidConfigException
     */
    public function getProviderInstance(): ?Provider
    {
        if ($this->providerInstance instanceof Provider) {
            return $this->providerInstance;
        }

        $config = [
            'app' => $this,
            'type' => $this->provider
        ];
        $this->providerInstance = Plugin::getInstance()->providers->createProvider($config);
        return $this->providerInstance;
    }

    /**
     * Get all token models belong to this app
     *
     * @return Token[]
     */
    public function getAllTokens(): array
    {
        return Plugin::getInstance()->tokens->getAllTokensForApp($this->id);
    }

    /**
     * Gets an ActiveQuery for tokens belonging to this app.
     *
     * @return ActiveQuery
     */
    public function getTokenRecordQuery(): ActiveQuery
    {
        return TokenRecord::find()->where(['appId' => $this->id]);
    }

    /**
     * Renders some basic UI to allow a user to connect to the app
     *
     * @param string|null $context
     * @return Markup
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws \Throwable
     * @throws \yii\base\Exception
     */
    public function renderConnector(string|null $context = null): Markup
    {
        $tokens = $this->getValidTokensForUser();
        $template = Craft::$app->view->renderTemplate('oauthclient/_connector/connector', [
            'context' => $context,
            'app' => $this,
            'token' => count($tokens) ? $tokens[0] : null
        ], View::TEMPLATE_MODE_CP);
        return Template::raw($template);
    }

    /**
     * Get all tokens valid tokens for the supplied user. If no user is supplied, the current user will
     * be used.
     *
     * @param int|User|null $user
     * @return Token[]
     * @throws Exception
     * @throws \Throwable
     */
    public function getValidTokensForUser(int|User $user = null): array
    {
        $userId = null;
        if ($user instanceof User) {
            $userId = $user->id;
        } elseif (is_int($user)) {
            $userId = $user;
        } elseif ($currentUser = Craft::$app->user->getIdentity()) {
            $userId = $currentUser->id;
        } else {
            // No user, but let's return an empty array, so we don't break anything upstream
            return [];
        }
        return Plugin::getInstance()->credentials->getValidTokensForAppAndUser($this, $userId);
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['handle', 'name', 'clientId', 'clientSecret', 'provider'], 'required'],
            [
                ['handle'],
                UniqueValidator::class,
                'targetClass' => AppRecord::class,
                'targetAttribute' => ['handle']
            ]
        ];
    }
}
