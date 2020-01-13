<?php

namespace venveo\oauthclient\models;

use Craft;
use craft\base\Model;
use craft\elements\User;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\validators\UniqueValidator;
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
    public $uid;
    public $id;
    public $dateCreated;
    public $dateUpdated;
    public $scopes;
    public $userId;
    public $provider;
    public $name;
    public $handle;
    public $clientId;
    public $clientSecret;
    public $urlAuthorize;

    public $isNew;

    private $providerInstance = null;

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
        return Craft::parseEnv($this->clientId);
    }

    /**
     * Parse any environment variables and return the client secret
     *
     * @return string
     */
    public function getClientSecret(): string
    {
        return Craft::parseEnv($this->clientSecret);
    }

    /**
     * Returns the custom authorization URL.
     *
     * @return string
     */
    public function getUrlAuthorize(): string
    {
        return Craft::parseEnv($this->clientSecret);
    }

    /**
     * Get the scopes for the app
     *
     * @param bool $forTable If true, we'll format the output for Craft's table field
     * @return array
     */
    public function getScopes($forTable = false): array
    {
        if ($forTable) {
            return array_map(function ($scope) {
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
     * @param null|string $context A context that will be passed to the controller to help tag events for handling.
     * @return string
     */
    public function getRedirectUrl($context = null): string
    {
        return UrlHelper::cpUrl('oauthclient/authorize/' . $this->handle, [
            'context' => $context
        ]);
    }

    /**
     * Get an instance of the Provider
     *
     * @return Provider|null
     * @throws InvalidConfigException
     */
    public function getProviderInstance()
    {
        if ($this->providerInstance instanceof Provider) {
            return $this->providerInstance;
        }

        $config = [
            'app' => $this,
            'type' => $this->provider
        ];
        $this->providerInstance = Plugin::$plugin->providers->createProvider($config);
        return $this->providerInstance;
    }

    /**
     * Get all token models belong to this app
     *
     * @return Token[]
     */
    public function getAllTokens(): array
    {
        return Plugin::$plugin->tokens->getAllTokensForApp($this->id);
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
     * @return Markup
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws \yii\base\Exception
     */
    public function renderConnector($context = null)
    {
        $view = Craft::$app->getView();
        $oldTemplateMode = $view->getTemplateMode();
        if ($oldTemplateMode !== $view::TEMPLATE_MODE_CP) {
            $view->setTemplateMode($view::TEMPLATE_MODE_CP);
        }
        $tokens = $this->getValidTokensForUser();
        $template = Craft::$app->view->renderTemplate('oauthclient/_connector/connector', [
            'context' => $context,
            'app' => $this,
            'token' => count($tokens) ? $tokens[0] : null
        ]);
        $view->setTemplateMode($oldTemplateMode);
        return Template::raw($template);
    }

    /**
     * Get all tokens valid tokens for the supplied user. If no user is supplied, the current user will
     * be used.
     *
     * @param null|int|User $user
     * @return Token[]
     * @throws Exception
     */
    public function getValidTokensForUser($user = null)
    {
        $userId = null;
        if ($user instanceof User) {
            $userId = $user->id;
        } elseif (is_int($user)) {
            $userId = $user;
        } elseif ($currentUser = Craft::$app->user->getIdentity()) {
            $userId = $currentUser->id;
        } else {
            // No user, but let's return an empty array so we don't break anything upstream
            return [];
        }
        return Plugin::$plugin->credentials->getValidTokensForAppAndUser($this, $userId);
    }

    /**
     * @inheritdoc
     */
    public function rules()
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
