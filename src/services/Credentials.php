<?php
/**
 * OAuth 2.0 Client plugin for Craft CMS 3.x
 *
 * Simple OAuth 2.0 client
 *
 * @link      https://venveo.com
 * @copyright Copyright (c) 2018 Venveo
 */

namespace venveo\oauthclient\services;

use craft\base\Component;
use craft\elements\User;
use venveo\oauthclient\models\App as AppModel;
use venveo\oauthclient\models\Token as TokenModel;
use venveo\oauthclient\Plugin;

/**
 * @author    Venveo
 * @package   OauthClient
 * @since     1.0.0
 *
 */
class Credentials extends Component
{
    /**
     * @param $appHandle
     * @param $userId
     * @return TokenModel[]
     * @throws \Exception
     */
    public function getValidTokensForAppAndUser($appHandle, $userId = null): ?array
    {
        // Allow models or IDs against my better judgement
        if ($appHandle instanceof AppModel) {
            $app = $appHandle;
            $appHandle = $app->handle;
        } else {
            $app = Plugin::$plugin->apps->getAppByHandle($appHandle);
        }
        if ($userId instanceof User) {
            $userId = $userId->getId();
        }

        if (!$app instanceof AppModel) {
            throw new \Exception("App does not exist");
        }

        $query = $app->getTokenRecordQuery();

        if ($userId !== null) {
            $query->where(['userId' => $userId]);
        }
        $tokenRecords = $query->all();
        if (!count($tokenRecords)) {
            return [];
        }

        $tokenModels = [];
        foreach ($tokenRecords as $tokenRecord) {
            $tokenModel = Plugin::$plugin->tokens->createToken($tokenRecord);
            // We need to prune this token at some point
            if ($tokenModel->isExpired() && empty($tokenModel->refreshToken)) {
                continue;
            }
            if ($tokenModel->isExpired() && !$this->refreshToken($tokenModel)) {
                \Craft::error('Unable to refresh token: '.print_r($tokenModel, true), __METHOD__);
                continue;
            }

            $tokenModels[] = $tokenModel;
        }

        return $tokenModels;
    }


    /**
     * Attempts to refresh a token and save it to the database
     * @param TokenModel $tokenModel
     * @param null $app
     * @return bool
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     */
    public function refreshToken(TokenModel $tokenModel, $app = null): bool
    {
        if (!$tokenModel->refreshToken) {
            return false;
        }
        if (!$app instanceof AppModel) {
            $app = $tokenModel->getApp();
        }

        $app->getProviderInstance()->refreshToken($tokenModel);

        return Plugin::$plugin->tokens->saveToken($tokenModel);
    }
}
