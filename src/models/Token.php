<?php

namespace venveo\oauthclient\models;

use craft\base\Model;
use craft\elements\User;
use craft\helpers\DateTimeHelper;
use craft\helpers\UrlHelper;
use craft\validators\DateTimeValidator;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;
use venveo\oauthclient\models\App as AppModel;
use venveo\oauthclient\Plugin;

/**
 * Class App
 *
 * @since 2.0
 * @property mixed $refreshURL
 * @property mixed $token
 * @property mixed $values
 * @property mixed $expires
 * @property string $cpEditUrl
 */
class Token extends Model implements AccessTokenInterface
{
    public $id;
    public $dateCreated;
    public $dateUpdated;

    public $userId;
    public $appId;
    public $expiryDate;
    public $refreshToken;
    public $accessToken;
    public $uid;

    private $tokenValues;

    /** @var AppModel */
    private $app;
    /** @var User */
    private $user;

    public static function fromLeagueToken(AccessToken $token): self
    {
        return new self([
            'accessToken' => $token->getToken(),
            'refreshToken' => $token->getRefreshToken(),
            'expiryDate' => $token->getExpires()
        ]);
    }

    /**
     * Gets the user the token belongs to
     * @return User|null
     */
    public function getUser()
    {
        if ($this->user instanceof User) {
            return $this->user;
        }

        return $this->user = \Craft::$app->users->getUserById($this->userId);
    }

    /**
     * Gets the app this token belongs to
     * @return App|null
     */
    public function getApp()
    {
        if ($this->app instanceof AppModel) {
            return $this->app;
        }

        $this->app = Plugin::$plugin->apps->getAppById($this->appId);
        return $this->app;
    }

    /**
     * The internal URL in the CP to refresh this token
     * @return string
     */
    public function getRefreshURL()
    {
        return UrlHelper::cpUrl('oauthclient/authorize/refresh/' . $this->id);
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['accessToken', 'appId'], 'required'],
            [
                ['expiryDate'],
                DateTimeValidator::class
            ]
        ];
    }

    // These are the attributes required by the League token interface

    /**
     * @inheritDoc
     */
    public function getToken()
    {
        return $this->accessToken;
    }

    /**
     * @inheritDoc
     */
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    /**
     * @inheritDoc
     */
    public function getExpires()
    {
        return $this->expiryDate;
    }

    /**
     * @inheritDoc
     */
    public function hasExpired()
    {
        $expiryDate = DateTimeHelper::toDateTime($this->expiryDate);
        $now = DateTimeHelper::currentUTCDateTime();
        return $now >= $expiryDate;
    }

    /**
     * @inheritDoc
     */
    public function getValues()
    {
        return $this->tokenValues;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        return $this->accessToken;
    }
}
