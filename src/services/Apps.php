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
use venveo\oauthclient\base\Provider;
use venveo\oauthclient\models\App as AppModel;
use venveo\oauthclient\records\App as AppRecord;

/**
 * @author    Venveo
 * @package   OauthClient
 * @since     1.0.0
 *
 * @property \venveo\oauthclient\models\App[]|array $allApps
 */
class Apps extends Component
{

    /**
     * Returns all apps
     *
     * @return AppModel[] All apps
     */
    public function getAllApps(): array
    {
        $rows = $this->_createAppQuery()
            ->orderBy(['name' => SORT_ASC])
            ->all();

        $apps = [];

        foreach ($rows as $row) {
            $apps[$row['id']] = $this->createApp($row);
        }

        return $apps;
    }

    public function getAppById($id):?AppModel
    {
        $result = $this->_createAppQuery()
            ->where(['id' => $id])
            ->one();

        return $result ? $this->createApp($result) : null;
    }

    public function getAppByHandle($handle): ?AppModel {
        $result = $this->_createAppQuery()
            ->where(['handle' => $handle])
            ->one();

        return $result ? $this->createApp($result) : null;
    }

    public function createApp($config): AppModel
    {
        $app = new AppModel($config);
        $app->userId = $app->userId ?? \Craft::$app->user->getId();
        $app->siteId = $app->siteId ?? \Craft::$app->sites->getCurrentSite()->id;
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
                'siteId',
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
        } else {
            $record = new AppRecord();
        }

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
        $record->siteId = Craft::$app->sites->currentSite->id;

        $record->validate();
        $record->addErrors($record->getErrors());

        if (!$record->hasErrors()) {
            // Save it!
            $record->save(false);

            // Now that we have a record ID, save it on the model
            $app->id = $record->id;

            return true;
        }

        return false;
    }
}
