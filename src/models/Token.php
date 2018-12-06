<?php

namespace venveo\oauthclient\models;

use craft\base\SavableComponent;
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
