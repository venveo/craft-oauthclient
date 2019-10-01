<?php

namespace venveo\oauthclient\models;

use craft\base\SavableComponent;
use craft\helpers\UrlHelper;
use craft\validators\UniqueValidator;
use venveo\oauthclient\base\Provider;
use venveo\oauthclient\Plugin;
use venveo\oauthclient\records\App as AppRecord;

/**
 * Class App
 *
 * @since 2.0
 * @property string $cpEditUrl
 */
class App extends SavableComponent
{
    public $scopes;
    public $siteId;
    public $userId;
    public $provider;
    public $name;
    public $handle;
    public $clientId;
    public $clientSecret;

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

    public function getClientId(): string
    {
        return \Craft::alias($this->clientId);
    }

    public function getClientSecret(): string
    {
        return \Craft::alias($this->clientSecret);
    }

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
     * @return string
     */
    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('oauthclient/apps/' . $this->handle);
    }

    public function getRedirectUrl(): string
    {
        return UrlHelper::cpUrl('oauthclient/authorize/' . $this->handle);
    }

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

    public function getAllTokens()
    {
        return Plugin::$plugin->tokens->getAllTokensForApp($this->id);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTokenRecordQuery()
    {
        return \venveo\oauthclient\records\Token::find()->where(['appId' => $this->id]);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['siteId', 'userId', 'handle', 'name', 'clientId', 'clientSecret', 'provider'], 'required'],
            [
                ['handle'],
                UniqueValidator::class,
                'targetClass' => AppRecord::class,
                'targetAttribute' => ['handle']
            ]
        ];
    }
}
