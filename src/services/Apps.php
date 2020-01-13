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
use craft\events\ConfigEvent;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use ReflectionException;
use venveo\oauthclient\events\AppEvent;
use venveo\oauthclient\events\AuthorizationUrlEvent;
use venveo\oauthclient\models\App as AppModel;
use venveo\oauthclient\Plugin;
use yii\base\InvalidConfigException;
use yii\db\Exception;

/**
 * @author    Venveo
 * @package   OauthClient
 * @since     1.0.0
 *
 * @property AppModel[]|array $allApps
 */
class Apps extends Component
{

    const EVENT_BEFORE_APP_SAVED = 'EVENT_BEFORE_APP_SAVED';
    const EVENT_AFTER_APP_SAVED = 'EVENT_AFTER_APP_SAVED';

    const EVENT_BEFORE_APP_DELETED = 'EVENT_BEFORE_APP_DELETED';
    const EVENT_AFTER_APP_DELETED = 'EVENT_AFTER_APP_DELETED';

    const EVENT_GET_URL_OPTIONS = 'EVENT_GET_URL_OPTIONS';

    private $_APPS_BY_HANDLE = [];
    private $_APPS_BY_ID = [];
    private $_APPS_BY_UID = [];
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
            $this->_APPS_BY_UID[$app->uid] = $app;
            $this->_APPS_BY_HANDLE[$app->handle] = $app;
        }

        $this->_ALL_APPS_FETCHED = true;
        return $this->_APPS_BY_ID;
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
                'uid',
                'id',
                'provider',
                'name',
                'userId',
                'dateCreated',
                'dateUpdated',
                'clientId',
                'clientSecret',
                'urlAuthorize',
                'scopes',
                'handle'
            ])
            ->from(['{{%oauthclient_apps}}']);
    }

    /**
     * Build an App model from some settings
     *
     * @param $config
     * @return AppModel
     */
    public function createApp($config): AppModel
    {
        $app = new AppModel($config);
        $app->userId = $app->userId ?? Craft::$app->user->getId();
        return $app;
    }

    /**
     * @param $id
     * @return AppModel|null
     */
    public function getAppById($id)
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
            $this->_APPS_BY_UID[$app->uid] = $app;
            $this->_APPS_BY_HANDLE[$app->handle] = $app;
            return $this->_APPS_BY_ID[$app->id];
        }
        return null;
    }

    /**
     * @param $handle
     * @return AppModel|null
     */
    public function getAppByHandle($handle)
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
            $this->_APPS_BY_UID[$app->uid] = $app;
            $this->_APPS_BY_HANDLE[$app->handle] = $app;
            return $this->_APPS_BY_HANDLE[$app->handle];
        }
        return null;
    }

    /**
     * Generates the authorization URL for an App model
     *
     * @param AppModel $app
     * @param $state
     * @param $context |null
     * @return string|null
     * @throws ReflectionException
     * @throws InvalidConfigException
     */
    public function getAuthorizationUrlForApp(AppModel $app, $state, $context = null)
    {
        $options = ['state' => $state];

        $event = new AuthorizationUrlEvent([
            'context' => $context,
            'app' => $app,
            'options' => $options
        ]);

        $provider = $app->getProviderInstance();

        if ($this->hasEventHandlers(self::EVENT_GET_URL_OPTIONS)) {
            $this->trigger(self::EVENT_GET_URL_OPTIONS, $event);
        }
        if ($event->url) {
            $url = $event->url;
        } else {
            $url = $provider->getAuthorizeURL($event->options);
        }
        return $url;
    }

    /**
     * Deletes an app
     *
     * @param AppModel $app
     */
    public function deleteApp(AppModel $app)
    {
        if ($this->hasEventHandlers(self::EVENT_BEFORE_APP_DELETED)) {
            $this->trigger(self::EVENT_BEFORE_APP_DELETED, new AppEvent([
                'app' => $app,
            ]));
        }

        $path = Plugin::$PROJECT_CONFIG_KEY . ".apps.{$app->uid}";
        Craft::$app->projectConfig->remove($path);
    }

    /**
     * Saves an app.
     *
     * @param AppModel $app
     * @param bool $runValidation
     * @return bool
     * @throws \Exception
     */
    public function saveApp(AppModel $app, bool $runValidation = true): bool
    {
        $isNew = empty($app->id);

        // Ensure the product type has a UID
        if ($isNew) {
            $app->uid = StringHelper::UUID();
        } else if (!$app->uid) {
            $app->uid = Db::uidById('{{%oauthclient_apps}}', $app->id);
        }

        if ($this->hasEventHandlers(self::EVENT_BEFORE_APP_SAVED)) {
            $this->trigger(self::EVENT_BEFORE_APP_SAVED, new AppEvent([
                'app' => $app,
                'isNew' => $isNew,
            ]));
        }

        // Make sure it validates
        if ($runValidation && !$app->validate()) {
            return false;
        }

        // Save it to the project config
        $path = Plugin::$PROJECT_CONFIG_KEY . ".apps.{$app->uid}";
        Craft::$app->projectConfig->set($path, [
            'name' => $app->name,
            'handle' => $app->handle,
            'provider' => $app->provider,
            'clientId' => $app->clientId,
            'clientSecret' => $app->clientSecret,
            'urlAuthorize' => $app->urlAuthorize,
            'scopes' => $app->scopes,
        ]);

        if ($isNew) {
            $app->id = Db::idByUid('{{%oauthclient_apps}}', $app->uid);
        }

        return true;
    }

    /**
     * @param ConfigEvent $event
     * @throws Exception
     */
    public function handleUpdatedApp(ConfigEvent $event)
    {
        $uid = $event->tokenMatches[0];

        // Does this app exist?
        $id = (new Query())
            ->select(['id'])
            ->from('{{%oauthclient_apps}}')
            ->where(['uid' => $uid])
            ->scalar();

        $isNew = empty($id);

        // Insert or update its row
        if ($isNew) {
            Craft::$app->db->createCommand()
                ->insert('{{%oauthclient_apps}}', [
                    'uid' => $uid,
                    'name' => $event->newValue['name'],
                    'handle' => $event->newValue['handle'],
                    'provider' => $event->newValue['provider'],
                    'clientId' => $event->newValue['clientId'],
                    'clientSecret' => $event->newValue['clientSecret'],
                    'urlAuthorize' => $event->newValue['urlAuthorize'],
                    'scopes' => $event->newValue['scopes'],
                ])
                ->execute();
        } else {
            Craft::$app->db->createCommand()
                ->update('{{%oauthclient_apps}}', [
                    'name' => $event->newValue['name'],
                    'handle' => $event->newValue['handle'],
                    'provider' => $event->newValue['provider'],
                    'clientId' => $event->newValue['clientId'],
                    'clientSecret' => $event->newValue['clientSecret'],
                    'urlAuthorize' => $event->newValue['urlAuthorize'],
                    'scopes' => $event->newValue['scopes'],
                ], ['id' => $id])
                ->execute();
        }

        $app = $this->getAppByUid($uid);

        if ($this->hasEventHandlers(self::EVENT_AFTER_APP_SAVED)) {
            $event = new AppEvent([
                'app' => $app
            ]);
            $this->trigger(self::EVENT_AFTER_APP_SAVED, $event);
        }
    }

    // PROJECT CONFIG HANDLERS //

    /**
     * @param $uid
     * @return AppModel|null
     */
    public function getAppByUid($uid)
    {
        if (isset($this->_APPS_BY_UID[$uid])) {
            return $this->_APPS_BY_UID[$uid];
        }
        $result = $this->_createAppQuery()
            ->where(['uid' => $uid])
            ->one();

        $app = $result ? $this->createApp($result) : null;
        if ($app) {
            $this->_APPS_BY_ID[$app->id] = $app;
            $this->_APPS_BY_UID[$app->uid] = $app;
            $this->_APPS_BY_HANDLE[$app->handle] = $app;
            return $this->_APPS_BY_ID[$app->id];
        }
        return null;
    }

    /**
     * @param ConfigEvent $event
     * @throws Exception
     */
    public function handleRemovedApp(ConfigEvent $event)
    {
        $uid = $event->tokenMatches[0];

        $app = $this->getAppByUid($uid);

        if ($this->hasEventHandlers(self::EVENT_BEFORE_APP_DELETED)) {
            $event = new AppEvent([
                'app' => $app
            ]);
            $this->trigger(self::EVENT_BEFORE_APP_DELETED, $event);
        }

        // If that came back empty, we're done!
        if (!$app) {
            return;
        }

        // Delete its row
        Craft::$app->db->createCommand()
            ->delete('{{%oauthclient_apps}}', ['id' => $app->id])
            ->execute();

        if ($this->hasEventHandlers(self::EVENT_AFTER_APP_DELETED)) {
            $event = new AppEvent([
                'app' => $app
            ]);
            $this->trigger(self::EVENT_AFTER_APP_DELETED, $event);
        }
    }
}
