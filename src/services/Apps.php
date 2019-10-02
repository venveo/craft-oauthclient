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
use venveo\oauthclient\events\AppEvent;
use venveo\oauthclient\models\App as AppModel;
use venveo\oauthclient\records\App as AppRecord;

/**
 * @author    Venveo
 * @package   OauthClient
 * @since     1.0.0
 *
 * @property AppModel[]|array $allApps
 */
class Apps extends Component
{

    public const EVENT_BEFORE_APP_SAVED = 'EVENT_BEFORE_APP_SAVED';
    public const EVENT_AFTER_APP_SAVED = 'EVENT_AFTER_APP_SAVED';

    private $_APPS_BY_HANDLE = [];
    private $_APPS_BY_ID = [];
    private $_ALL_APPS_FETCHED = false;

    /**
     * Returns all apps
     *
     * @return AppModel[] All apps
     */
    public function getAllApps(): array
    {
        if ($this->_ALL_APPS_FETCHED) {
            return $this->_APPS_BY_ID;
        }

        $rows = $this->_createAppQuery()
            ->orderBy(['name' => SORT_ASC])
            ->all();

        foreach ($rows as $row) {
            $app = $this->createApp($row);
            $this->_APPS_BY_ID[$app->id] = $app;
            $this->_APPS_BY_HANDLE[$app->handle] = $app;
        }

        $this->_ALL_APPS_FETCHED = true;
        return $this->_APPS_BY_ID;
    }

    public function getAppById($id): ?AppModel
    {
        if (isset($this->_APPS_BY_ID[$id])) {
            return $this->_APPS_BY_ID[$id];
        }
        $result = $this->_createAppQuery()
            ->where(['id' => $id])
            ->one();

        $app = $result ? $this->createApp($result) : null;
        if ($app) {
            $this->_APPS_BY_ID[$app->id] = $app;
            $this->_APPS_BY_HANDLE[$app->handle] = $app;
            return $this->_APPS_BY_ID[$app->id];
        }
        return null;
    }

    public function getAppByHandle($handle): ?AppModel
    {
        if (isset($this->_APPS_BY_HANDLE[$handle])) {
            return $this->_APPS_BY_HANDLE[$handle];
        }
        $result = $this->_createAppQuery()
            ->where(['handle' => $handle])
            ->one();

        $app = $result ? $this->createApp($result) : null;
        if ($app) {
            $this->_APPS_BY_ID[$app->id] = $app;
            $this->_APPS_BY_HANDLE[$app->handle] = $app;
            return $this->_APPS_BY_HANDLE[$app->handle];
        }
        return null;
    }

    /**
     * @param $config
     * @return AppModel
     */
    public function createApp($config): AppModel
    {
        $app = new AppModel($config);
        $app->userId = $app->userId ?? \Craft::$app->user->getId();
        return $app;
    }


    /**
     * Returns a Query object prepped for retrieving gateways.
     *
     * @return Query The query object.
     */
    private function _createAppQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'provider',
                'name',
                'userId',
                'dateCreated',
                'dateUpdated',
                'clientId',
                'clientSecret',
                'scopes',
                'handle'
            ])
            ->from(['{{%oauthclient_apps}}']);
    }


    /**
     * Saves an app.
     *
     * @param AppModel $app
     * @param bool $runValidation
     * @return bool
     */
    public function saveApp(AppModel $app, bool $runValidation = true): bool
    {
        if ($app->id) {
            $record = AppRecord::findOne($app->id);

            if (!$record) {
                throw new \Exception(\Craft::t('oauthclient', 'No app exists with the ID “{id}”', ['id' => $app->id]));
            }
            $app->isNew = false;
        } else {
            $app->isNew = true;
            $record = new AppRecord();
        }

        $event = new AppEvent();
        $event->app = $app;
        $this->trigger(self::EVENT_BEFORE_APP_SAVED, $event);

        if ($runValidation && !$app->validate()) {
            Craft::info('App not saved due to validation error.', __METHOD__);

            return false;
        }
        $record->name = $app->name;
        $record->handle = $app->handle;
        $record->clientSecret = $app->clientSecret;
        $record->clientId = $app->clientId;
        $record->provider = $app->provider;
        $record->scopes = $app->scopes;

        $record->validate();
        $record->addErrors($record->getErrors());

        if (!$record->hasErrors()) {
            // Save it!
            $record->save(false);
            // Now that we have a record ID, save it on the model
            $app->id = $record->id;

            $this->trigger(self::EVENT_AFTER_APP_SAVED, $event);

            return true;
        }

        return false;
    }
}
