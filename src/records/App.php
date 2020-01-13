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
use yii\db\ActiveQueryInterface;

/**
 * @author    Venveo
 * @package   OauthClient
 * @since     1.0.0
 * @property int $id
 * @property \DateTime $dateCreated
 * @property \DateTime $dateUpdated
 * @property string $uid
 * @property int $userId
 * @property string $name
 * @property string $provider
 * @property string $handle
 * @property string $clientId
 * @property string $clientSecret
 * @property string $urlAuthorize
 * @property User $user
 * @property \yii\db\ActiveQueryInterface $tokens
 * @property string $scopes
 */
class App extends ActiveRecord
{
    // Public Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%oauthclient_apps}}';
    }

    // Public Methods
    // =========================================================================

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
    public function getTokens(): ActiveQueryInterface
    {
        return $this->hasMany(Token::class, ['id' => 'appId']);
    }
}
