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
use yii\db\ActiveQueryInterface;

/**
 * @author    Venveo
 * @package   OauthClient
 * @since     1.0.0
 * @property int $id
 * @property DateTime $dateCreated
 * @property DateTime $dateUpdated
 * @property string $uid
 * @property string $name
 * @property string $provider
 * @property string $handle
 * @property string $clientId
 * @property string $clientSecret
 * @property string $urlAuthorize
 * @property User $user
 * @property ActiveQueryInterface $tokens
 * @property string $scopes
 */
class App extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%oauthclient_apps}}';
    }


    /**
     * @return ActiveQueryInterface
     */
    public function getTokens(): ActiveQueryInterface
    {
        return $this->hasMany(Token::class, ['id' => 'appId']);
    }
}
