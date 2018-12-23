<?php

namespace venveo\oauthclient\models;

use craft\elements\User;
use craft\helpers\UrlHelper;
use venveo\oauthclient\OauthClient as Plugin;
use venveo\oauthclient\models\App as AppModel;
use craft\base\SavableComponent;
use craft\helpers\DateTimeHelper;
use craft\validators\DateTimeValidator;
use League\OAuth2\Client\Token\AccessToken;

/**
 * Class App
 *
 * @since 2.0
 * @property string $cpEditUrl
 */
class Token extends SavableComponent
{
    public $siteId;
    public $userId;
    public $appId;
    public $expiryDate;
    public $refreshToken;
    public $accessToken;
    public $uid;

    private $app;
    private $user;

    /**
     * Returns the name of this payment method.
     *
     * @return string
     */
    public function __toString()
    {
        return (string)$this->accessToken;
    }

    public static function fromLeagueToken(AccessToken $token): self
    {
        return new self([
            'accessToken' => $token->getToken(),
            'refreshToken' => $token->getRefreshToken(),
            'expiryDate' => $token->getExpires()
        ]);
    }

    public function getUser()
    {
        if ($this->user instanceof User) {
            return $this->user;
        }

        $this->user = \Craft::$app->users->getUserById($this->userId);
        return $this->user;
    }

    public function getApp() {
        if ($this->app instanceof AppModel) {
            return $this->app;
        }

        $this->app = Plugin::$plugin->apps->getAppById($this->appId);
        return $this->app;
    }

    public function isExpired(): bool
    {
        $expiryDate = DateTimeHelper::toDateTime($this->expiryDate);
        $now = DateTimeHelper::currentUTCDateTime();
        $expired = $now >= $expiryDate;
        return $expired;
    }

    public function getRefreshURL() {
        return UrlHelper::cpUrl('oauthclient/authorize/refresh/'.$this->id);
    }



    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['siteId', 'accessToken', 'appId'], 'required'],
            [
                ['expiryDate'],
                DateTimeValidator::class
            ]
        ];
    }
}
