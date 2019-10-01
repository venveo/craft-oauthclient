<?php
/**
 * OAuth 2.0 Client plugin for Craft CMS 3.x
 *
 * Simple OAuth 2.0 client
 *
 * @link      https://venveo.com
 * @copyright Copyright (c) 2018 Venveo
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

/**
 * @author    Venveo
 * @package   OauthClient
 * @since     1.0.0
 */
class AppsController extends Controller
{

    public function actionIndex()
    {
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

        // Make sure the selected gateway class is in there
//        if ($variables['app']->provider && !in_array($variables['app']->provider, $allProviderTypes, true)) {
//            $allGatewayTypes[] = get_class($gateway);
//        }
        $providerOptions = [];
        $providerInstances = [];
//
        foreach ($allProviderTypes as $class) {
//            if (($gateway && $class === get_class($gateway)) || $class::isSelectable()) {
            $providerInstances[$class] = $providersService->createProvider($class);
//
            $providerOptions[] = [
                'value' => $class,
                'label' => $class::displayName()
            ];
        }
//        }

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
     * @return Response|null
     * @throws HttpException
     */
    public function actionSave()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $gatewayService = Plugin::getInstance()->apps;

        $scopes = $request->getBodyParam('scopes');
        $scopes = implode(',', ArrayHelper::getColumn($scopes, 'scope'));

        $config = [
            'id' => $request->getBodyParam('id'),
            'provider' => $request->getBodyParam('provider'),
            'name' => $request->getBodyParam('name'),
            'handle' => $request->getBodyParam('handle'),
            'clientId' => $request->getBodyParam('clientId'),
            'clientSecret' => $request->getBodyParam('clientSecret'),
            'scopes' => $scopes
        ];

        /** @var AppModel $app */
        $app = $gatewayService->createApp($config);

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
