<?php
/**
 * OAuth 2.0 Client plugin for Craft CMS 3.x
 *
 * Simple OAuth 2.0 client
 *
 * @link      https://venveo.com
 * @copyright Copyright (c) 2018-2019 Venveo
 */

namespace venveo\oauthclient\services;

use Craft;
use craft\base\Component;
use craft\elements\User;
use venveo\oauthclient\base\Provider;
use venveo\oauthclient\base\ValidatesTokens;
use venveo\oauthclient\events\TokenEvent;
use venveo\oauthclient\models\App as AppModel;
use venveo\oauthclient\models\Token;
use venveo\oauthclient\models\Token as TokenModel;
use venveo\oauthclient\Plugin;
use yii\db\Exception;

/**
 * @author    Venveo
 * @package   OauthClient
 * @since     1.0.0
 *
 */
class Credentials extends Component
{
    public const EVENT_BEFORE_REFRESH_TOKEN = 'EVENT_BEFORE_REFRESH_TOKEN';
    public const EVENT_AFTER_REFRESH_TOKEN = 'EVENT_BEFORE_REFRESH_TOKEN';
    public const EVENT_TOKEN_REFRESH_FAILED = 'EVENT_TOKEN_REFRESH_FAILED';

    /**
     * Gets valid tokens given an application and optionally, a Craft user ID
     * This method will attempt to refresh expired tokens for an app
     * @param $appHandle AppModel|string
     * @param $user User|int
     * @return TokenModel[]
     * @throws \Exception
     */
    public function getValidTokensForAppAndUser($appHandle, $user = null): array
    {
        // Allow models or IDs against my better judgement
        if ($appHandle instanceof AppModel) {
            $app = $appHandle;
        } else {
            $app = Plugin::$plugin->apps->getAppByHandle($appHandle);
        }

        if (!$app instanceof AppModel) {
            throw new \Exception('App does not exist');
        }

        $userId = null;

        if ($user instanceof User) {
            $userId = $user->getId();
        } elseif (is_int($user)) {
            $userId = $user;
        }

        $query = $app->getTokenRecordQuery();

        if ($userId !== null) {
            $query->andWhere(['userId' => $userId]);
        }
        $tokenRecords = $query->all();
        if (!count($tokenRecords)) {
            return [];
        }

        $tokenModels = [];
        foreach ($tokenRecords as $tokenRecord) {
            $tokenModel = Plugin::$plugin->tokens->createToken($tokenRecord);
            // We need to prune this token at some point
            if ($tokenModel->hasExpired() && empty($tokenModel->refreshToken)) {
                continue;
            }
            if ($tokenModel->hasExpired() && !$this->refreshToken($tokenModel)) {
                Craft::error('Unable to refresh token: '.print_r($tokenModel, true), __METHOD__);
                continue;
            }

            $tokenModels[] = $tokenModel;
        }

        return $tokenModels;
    }


    /**
     * Attempts to refresh a token and save it to the database
     * @param TokenModel $tokenModel
     * @return bool
     */
    public function refreshToken(TokenModel $tokenModel): bool
    {
        $event = new TokenEvent($tokenModel);
        $this->trigger(self::EVENT_BEFORE_REFRESH_TOKEN, $event);

        if (!$tokenModel->refreshToken) {
            return false;
        }
        try {
            $app = $tokenModel->getApp();
            $app->getProviderInstance()->refreshToken($tokenModel);
            $saved = Plugin::$plugin->tokens->saveToken($tokenModel);
            if ($saved) {
                $this->trigger(self::EVENT_AFTER_REFRESH_TOKEN, $event);
            } else {
                throw new Exception('Failed to save refreshed token');
            }
            return $saved;
        } catch (\Exception $exception) {
            Craft::warning($exception->getMessage(), __METHOD__);
            $this->trigger(self::EVENT_TOKEN_REFRESH_FAILED, $event);
            return false;
        }
    }

    /**
     * If the token's Provider implements ValidatesToken, we can ask it to verify the token with the provider
     *
     * @param TokenModel $token
     * @return bool
     * @throws \yii\base\InvalidConfigException
     */
    public function checkTokenWithProvider(Token $token) {
        $app = $token->getApp();

        /** @var Provider $provider */
        $provider = $app->getProviderInstance();
        if (!$provider instanceof ValidatesTokens) {
            throw new \Exception('Provider cannot validate tokens');
        }
        return $provider::checkToken($token);
    }
}
