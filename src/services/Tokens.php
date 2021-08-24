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
use Exception;
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

    const EVENT_BEFORE_TOKEN_SAVED = 'EVENT_BEFORE_TOKEN_SAVED';
    const EVENT_AFTER_TOKEN_SAVED = 'EVENT_AFTER_TOKEN_SAVED';

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

    public function createToken($config): TokenModel
    {
        $app = new TokenModel($config);
        $app->userId = $app->userId ?? Craft::$app->user->getId();
        return $app;
    }

    /**
     * @param $id
     * @return TokenModel|null
     */
    public function getTokenById($id)
    {
        $result = $this->_createTokenQuery()
            ->where(['id' => $id])
            ->one();

        return $result ? $this->createToken($result) : null;
    }

    public function getAllTokensForApp($appId): array
    {
        $rows = $this->_createTokenQuery()
            ->where(['appId' => $appId])
            ->all();

        $tokens = [];

        foreach ($rows as $row) {
            $tokens[$row['id']] = $this->createToken($row);
        }

        return $tokens;
    }

    /**
     * Saves a token.
     *
     * @param TokenModel $token
     * @param bool $runValidation
     * @return bool
     * @throws Exception
     */
    public function saveToken(TokenModel $token, bool $runValidation = true, $prune = true): bool
    {
        $isNew = empty($token->id);
        if ($token->id) {
            $record = TokenRecord::findOne($token->id);

            if (!$record) {
                throw new Exception(Craft::t('oauthclient', 'No token exists with the ID “{id}”', ['id' => $token->id]));
            }
        } else {
            $record = new TokenRecord();
        }

        if ($this->hasEventHandlers(self::EVENT_BEFORE_TOKEN_SAVED)) {
            $this->trigger(self::EVENT_BEFORE_TOKEN_SAVED, new TokenEvent([
                'token' => $token,
                'isNew' => $isNew
            ]));
        }

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

            if ($prune && $record->userId) {
                $deleted = TokenRecord::deleteAll(['and',
                    ['=', 'userId', $record->userId],
                    ['=', 'appId', $record->appId],
                    ['!=', 'id', $token->id]
                ]);
                Craft::info("Pruned $deleted tokens during token save for user: ". $record->userId);
            }

            if ($this->hasEventHandlers(self::EVENT_AFTER_TOKEN_SAVED)) {
                $this->trigger(self::EVENT_AFTER_TOKEN_SAVED, new TokenEvent([
                    'token' => $token,
                    'isNew' => $isNew
                ]));
            }

            return true;
        }

        return false;
    }
}
