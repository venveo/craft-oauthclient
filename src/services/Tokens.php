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

use Craft;
use craft\base\Component;
use craft\db\Query;
use venveo\oauthclient\events\TokenEvent;
use venveo\oauthclient\models\Token;
use venveo\oauthclient\models\Token as TokenModel;
use venveo\oauthclient\records\Token as TokenRecord;

/**
 * @author    Venveo
 * @package   OauthClient
 * @since     1.0.0
 *
 *
 * @property TokenModel[]|array $allTokens
 */
class Tokens extends Component
{

    public const EVENT_BEFORE_TOKEN_SAVED = 'EVENT_BEFORE_TOKEN_SAVED';
    public const EVENT_AFTER_TOKEN_SAVED = 'EVENT_AFTER_TOKEN_SAVED';

    /**
     * Returns all tokens
     *
     * @return TokenModel[] All tokens
     */
    public function getAllTokens(): array
    {
        $rows = $this->_createTokenQuery()
            ->all();

        $tokens = [];

        foreach ($rows as $row) {
            $tokens[$row['id']] = $this->createToken($row);
        }

        return $tokens;
    }

    public function getTokenById($id): ?TokenModel
    {
        $result = $this->_createTokenQuery()
            ->where(['id' => $id])
            ->one();

        return $result ? $this->createToken($result) : null;
    }

    public function getAllTokensForApp($appId): array {
        $rows = $this->_createTokenQuery()
            ->where(['appId' => $appId])
            ->all();

        $tokens = [];

        foreach ($rows as $row) {
            $tokens[$row['id']] = $this->createToken($row);
        }

        return $tokens;
    }

    public function createToken($config): TokenModel
    {
        $app = new TokenModel($config);
        $app->userId = $app->userId ?? \Craft::$app->user->getId();
        return $app;
    }


    /**
     * Returns a Query object prepped for retrieving tokens.
     *
     * @return Query The query object.
     */
    private function _createTokenQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'userId',
                'dateCreated',
                'dateUpdated',
                'expiryDate',
                'appId',
                'accessToken',
                'refreshToken',
            ])
            ->from(['{{%oauthclient_tokens}}']);
    }


    /**
     * Saves a token.
     *
     * @param TokenModel $token
     * @param bool $runValidation
     * @return bool
     * @throws \Exception
     */
    public function saveToken(TokenModel $token, bool $runValidation = true): bool
    {
        if ($token->id) {
            $record = TokenRecord::findOne($token->id);

            if (!$record) {
                throw new \Exception(\Craft::t('oauthclient', 'No token exists with the ID “{id}”', ['id' => $token->id]));
            }
        } else {
            $record = new TokenRecord();
        }

        $event = new TokenEvent();
        $event->token = $token;
        $this->trigger(self::EVENT_BEFORE_TOKEN_SAVED, $event);

        if ($runValidation && !$token->validate()) {
            Craft::info('Token not saved due to validation error.', __METHOD__);

            return false;
        }
        $record->accessToken = $token->accessToken;
        $record->refreshToken = $token->refreshToken;
        $record->expiryDate = $token->expiryDate;
        $record->userId = $token->userId;
        $record->appId = $token->appId;

        $record->validate();
        $record->addErrors($record->getErrors());

        if (!$record->hasErrors()) {
            // Save it!
            $record->save(false);

            // Now that we have a record ID, save it on the model
            $token->id = $record->id;

            $this->trigger(self::EVENT_AFTER_TOKEN_SAVED, $event);

            return true;
        }

        return false;
    }
}
