<?php

namespace venveo\oauthclient\models;

use Craft;
use craft\base\Model;
use craft\elements\User;
use craft\helpers\UrlHelper;
use craft\validators\DateTimeValidator;
use DateTime;
use League\OAuth2\Client\Token\AccessTokenInterface;
use venveo\oauthclient\Plugin;

/**
 * Class App
 *
 * @since 2.0
 * @property mixed $refreshURL
 * @property mixed $token
 * @property mixed $values
 * @property mixed $expires
 * @property-read null|App $app
 * @property string $cpEditUrl
 */
class Token extends Model implements AccessTokenInterface
{
    public ?int $id = null;
    public ?\DateTime $dateCreated = null;
    public ?\DateTime $dateUpdated = null;

    public ?int $userId = null;
    public ?int $appId = null;
    public ?\DateTime $expiryDate = null;
    public ?string $refreshToken = null;
    public ?string $accessToken = null;
    public ?string $uid = null;

    private ?array $tokenValues = null;
    private ?User $user = null;

    /**
     * Gets the user the token belongs to
     * @return User|null
     */
    public function getUser()
    {
        if ($this->user instanceof User) {
            return $this->user;
        }

        return $this->user = Craft::$app->users->getUserById($this->userId);
    }

    /**
     * Gets the app this token belongs to
     * @return App|null
     */
    public function getApp()
    {
        return Plugin::getInstance()->apps->getAppById($this->appId);
    }

    /**
     * The internal URL in the CP to refresh this token
     * @return string
     */
    public function getRefreshURL(): string
    {
        return UrlHelper::cpUrl('oauth/authorize/refresh/' . $this->id);
    }


    /**
     * @inheritdoc
     */
    public function rules(): array
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
    public function getToken(): ?string
    {
        return $this->accessToken;
    }

    /**
     * @inheritDoc
     */
    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    /**
     * @inheritDoc
     */
    public function getExpires(): DateTime|int|null
    {
        return $this->expiryDate;
    }

    /**
     * @inheritDoc
     */
    public function hasExpired(): bool
    {
        if (!$this->expiryDate) {
            return false;
        }

        $now = new DateTime();
        $expiryDate = $this->expiryDate;

        return $now->getTimestamp() > $expiryDate->getTimestamp();
    }

    /**
     * @inheritDoc
     */
    public function getValues(): array
    {
        return $this->tokenValues;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        return $this?->accessToken ?? '';
    }
}
