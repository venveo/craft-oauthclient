<?php
/**
 *  OAuth 2.0 Client plugin for Craft CMS 3
 * @link      https://www.venveo.com
 * @copyright Copyright (c) 2018-2019 Venveo
 */

namespace venveo\oauthclient\controllers;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use craft\web\Response;
use Exception;
use venveo\oauthclient\models\App as AppModel;
use venveo\oauthclient\Plugin;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;

/**
 * @author    Venveo
 * @package   OauthClient
 * @since     1.0.0
 */
class AppsController extends Controller
{

    /**
     * @return \yii\web\Response
     * @throws ForbiddenHttpException
     */
    public function actionIndex(): \yii\web\Response
    {
        $this->requireAdmin();
        $apps = Plugin::getInstance()->apps->getAllApps();

        return $this->renderTemplate('oauthclient/apps/index.twig', [
            'apps' => $apps
        ]);
    }

    /**
     * @param $handle
     * @param $app
     * @return \yii\web\Response
     * @throws ForbiddenHttpException
     */
    public function actionEdit($handle = null, $app = null): \yii\web\Response
    {
        if (!Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            throw new ForbiddenHttpException('Administrative changes are disallowed in this environment');
        }
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
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionDelete(): \yii\web\Response
    {
        if (!Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            throw new ForbiddenHttpException('Administrative changes are disallowed in this environment');
        }
        $this->requireAdmin();
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $id = $request->getRequiredBodyParam('id');
        $app = Plugin::getInstance()->apps->getAppById($id);

        if (!$app) {
            throw new NotFoundHttpException('App does not exist');
        }

        Plugin::getInstance()->apps->deleteApp($app);

        return $this->asJson(['success' => true]);
    }

    /**
     * @return \yii\web\Response|null
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     */
    public function actionSave(): ?\yii\web\Response
    {
        if (!Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            throw new ForbiddenHttpException('Administrative changes are disallowed in this environment');
        }
        $isNew = true;
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
            'urlAuthorize' => $request->getRequiredBodyParam('urlAuthorize'),
            'scopes' => $scopes
        ];

        $app = $appService->createApp($config);
        if ($app->id) {
            $isNew = false;
        }

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
        return $this->redirect(UrlHelper::cpUrl('oauthclient/apps/' . $app->handle . ($isNew ? '#info-tab' : '')));
    }
}
