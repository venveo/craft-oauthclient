<?php
/**
 * OAuth 2.0 Client plugin for Craft CMS 3.x
 *
 * Simple OAuth 2.0 client
 *
 * @link      https://venveo.com
 * @copyright Copyright (c) 2018 Venveo
 */

namespace venveo\oauthclient\records;

use craft\db\ActiveRecord;
use craft\records\User;
use DateTime;
use venveo\oauthclient\records\App as AppRecord;
use yii\db\ActiveQueryInterface;

/**
 * @author    Venveo
 * @package   OauthClient
 * @since     1.0.0
 *
 * @property int $id
 * @property DateTime $dateCreated
 * @property DateTime $dateUpdated
 * @property DateTime $expiryDate
 * @property string $uid
 * @property int $userId
 * @property int $appId
 * @property string $accessToken
 * @property string $refreshToken
 * @property App $app
 * @property User $user
 */
class Token extends ActiveRecord
{
    // Public Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%oauthclient_tokens}}';
    }

    /**
     * Returns the OAuth Tokens’s user.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getUser(): ActiveQueryInterface
    {
        return $this->hasOne(User::class, ['id' => 'userId']);
    }

    /**
     * Returns the OAuth Tokens’s user.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getApp(): ActiveQueryInterface
    {
        return $this->hasOne(AppRecord::class, ['id' => 'appId']);
    }

    /**
     * @inheritdoc
     */
    public function datetimeAttributes(): array
    {
        $attributes = parent::datetimeAttributes();

        $attributes[] = 'expiryDate';

        return $attributes;
    }
}
