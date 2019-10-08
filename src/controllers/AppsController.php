<?php
/**
 *  OAuth 2.0 Client plugin for Craft CMS 3
 *  @link      https://www.venveo.com
 *  @copyright Copyright (c) 2018-2019 Venveo
 */

namespace venveo\oauthclient\controllers;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use craft\web\Response;
use venveo\oauthclient\models\App as AppModel;
use venveo\oauthclient\Plugin;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;

/**
 * @author    Venveo
 * @package   OauthClient
 * @since     1.0.0
 */
class AppsController extends Controller
{

    public function actionIndex()
    {
        $this->requireAdmin();
        $apps = Plugin::getInstance()->apps->getAllApps();

        return $this->renderTemplate('oauthclient/apps/index.twig', [
            'apps' => $apps
        ]);
    }


    /**
     * @param AppModel|null $app
     * @return Response
     * @throws HttpException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionEdit($handle = null, $app = null)
    {
        $this->requireAdmin();
        $variables = [
            'handle' => $handle,
            'app' => $app
        ];

        $appService = Plugin::getInstance()->apps;
        $providersService = Plugin::getInstance()->providers;

        if (!$variables['app'] && $variables['handle']) {
            $variables['app'] = $appService->getAppByHandle($variables['handle']);
        }
        if (!$variables['app']) {
            $variables['app'] = $appService->createApp([]);
        }


        /** @var string[] $allProviderTypes */
        $allProviderTypes = $providersService->getAllProviderTypes();

        $providerOptions = [];
        foreach ($allProviderTypes as $class) {
            $providerOptions[] = [
                'value' => $class,
                'label' => $class::displayName()
            ];
        }

        $variables['providerTypes'] = $allProviderTypes;
        $variables['providerOptions'] = $providerOptions;

        if ($variables['app']->id) {
            $variables['title'] = $variables['app']->name;
        } else {
            $variables['title'] = Craft::t('oauthclient', 'Create a new OAuth App');
        }
        return $this->renderTemplate('oauthclient/apps/_edit', $variables);
    }

    /**
     * Attempt to delete an app by its ID
     * @return \yii\web\Response
     * @throws \yii\db\Exception
     * @throws \yii\web\BadRequestHttpException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionDelete() {
        $this->requireAdmin();
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $id = $request->getRequiredBodyParam('id');
        $app = Plugin::$plugin->apps->getAppById($id);

        if (!$app) {
            throw new NotFoundHttpException('App does not exist');
        }

        Plugin::$plugin->apps->deleteApp($app);

        return $this->asJson(['success' => true]);
    }

    /**
     * @return Response|null
     * @throws HttpException
     * @throws \Exception
     */
    public function actionSave()
    {
        $this->requireAdmin();
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $appService = Plugin::getInstance()->apps;

        $scopes = $request->getBodyParam('scopes');
        $scopes = implode(',', ArrayHelper::getColumn($scopes, 'scope'));

        $config = [
            'id' => $request->getBodyParam('id'),
            'provider' => $request->getRequiredBodyParam('provider'),
            'name' => $request->getRequiredBodyParam('name'),
            'handle' => $request->getRequiredBodyParam('handle'),
            'clientId' => $request->getRequiredBodyParam('clientId'),
            'clientSecret' => $request->getRequiredBodyParam('clientSecret'),
            'scopes' => $scopes
        ];

        /** @var AppModel $app */
        $app = $appService->createApp($config);

        $session = Craft::$app->session;

        // Save it
        if (!Plugin::getInstance()->apps->saveApp($app)) {
            $session->setError(Craft::t('oauthclient', 'Failed to save app'));
            // Send the volume back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'app' => $app
            ]);
            return null;
        }

        $session->setNotice(Craft::t('oauthclient', 'App saved'));
        return $this->redirect(UrlHelper::cpUrl('oauthclient/apps/' . $app->handle . ($app->isNew ? '#info-tab' : '')));
    }
}
