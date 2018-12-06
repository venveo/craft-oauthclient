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
use craft\records\Site;
use craft\records\User;
use venveo\oauthclient\records\App as AppRecord;
use yii\db\ActiveQueryInterface;

/**
 * @author    Venveo
 * @package   OauthClient
 * @since     1.0.0
 *
 * @property int $id
 * @property \DateTime $dateCreated
 * @property \DateTime $dateUpdated
 * @property \DateTime $expiryDate
 * @property string $uid
 * @property int $siteId
 * @property int $userId
 * @property int $appId
 * @property string $accessToken
 * @property string $refreshToken
 * @property App $app
 * @property Site $site
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
     * @return ActiveQueryInterface
     */
    public function getSite(): ActiveQueryInterface
    {
        return $this->hasOne(Site::class, ['id' => 'siteId']);
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
}
