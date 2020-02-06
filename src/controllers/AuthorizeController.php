<?php
/**
 *  OAuth 2.0 Client plugin for Craft CMS 3
 * @link      https://www.venveo.com
 * @copyright Copyright (c) 2018-2019 Venveo
 */

namespace venveo\oauthclient\controllers;

use Craft;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use craft\web\Response;
use Exception;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use ReflectionException;
use venveo\oauthclient\base\Provider;
use venveo\oauthclient\models\App as AppModel;
use venveo\oauthclient\Plugin;

/**
 * @author    Venveo
 * @package   Oauth20Client
 * @since     1.0.0
 */
class AuthorizeController extends Controller
{
    const STATE_SESSION_KEY = 'oauth2state';

    /**
     * Handles the actual OAuth process
     * @param string $handle
     * @return Response
     * @throws IdentityProviderException
     * @throws ReflectionException
     * @throws Exception
     */
    public function actionAuthorizeApp($handle): Response
    {
        if (Craft::$app->request->isPost) {
            $redirectUrl = Craft::$app->getRequest()->getValidatedBodyParam('redirect');
            if ($redirectUrl) {
                Craft::$app->session->set('OAUTH_REDIRECT_URL', $redirectUrl);
            }
        }

        /** @var  $app */
        $app = Plugin::$plugin->apps->getAppByHandle($handle);
        if (!$app instanceof AppModel) {
            Craft::$app->response->setStatusCode(404, 'App handle does not exist');
            return null;
        }

        $this->requirePermission('oauthclient-login:' . $app->uid);

        $context = Craft::$app->request->getParam('context');
        $error = Craft::$app->request->getParam('error');
        $code = Craft::$app->request->getParam('code');
        $state = Craft::$app->request->getParam('state');

        /** @var Provider $provider */
        $provider = $app->getProviderInstance();

        // OAuth provider sent back an error
        if (!empty($error)) {
            Craft::error($error, __METHOD__);
            Craft::$app->session->remove(self::STATE_SESSION_KEY);
            Craft::$app->session->setError(Craft::t('oauthclient', 'Failed to authorize app: ' . $error));
            return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('oauthclient/apps'));
        }

        // Begin auth process
        if (empty($code)) {
            $state = $this->getRandomState();
            Craft::$app->session->set(self::STATE_SESSION_KEY, $state);

            $url = Plugin::$plugin->apps->getAuthorizationUrlForApp($app, $state, $context);

            return Craft::$app->response->redirect($url);
        }

        if (empty($state) || Craft::$app->session->get(self::STATE_SESSION_KEY) !== $state) {

            Craft::$app->session->setError(Craft::t('oauthclient', 'Invalid OAuth 2 State'));
            Craft::$app->session->remove(self::STATE_SESSION_KEY);
            return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('oauthclient/apps'));
        }
        /** @var Provider $configuredProvider */
        $configuredProvider = $provider->getConfiguredProvider();
        $tokenResponse = $configuredProvider->getAccessToken('authorization_code', [
            'code' => $code
        ]);

        // We need to save the token
        $token = $provider->createTokenModelFromResponse($tokenResponse);
        $token->appId = $app->id;
        $token->userId = Craft::$app->user->getId();

        try {
            $saved = Plugin::$plugin->tokens->saveToken($token);
            if ($saved) {
                Craft::$app->session->setFlash(Craft::t('oauthclient', 'Connected via ' . $app->name));
            } else {
                Craft::$app->session->setError(Craft::t('oauthclient', 'Failed to save token'));
            }
        } catch (Exception $e) {
            Craft::error($e->getTraceAsString(), __METHOD__);
            Craft::$app->session->setError(Craft::t('oauthclient', 'Something went wrong: ' . $e->getMessage()));
        }

        $redirectUrl = Craft::$app->session->get('OAUTH_REDIRECT_URL');
        if ($redirectUrl) {
            Craft::$app->session->remove('OAUTH_REDIRECT_URL');
            return Craft::$app->getResponse()->redirect(UrlHelper::url($redirectUrl));
        }

        return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('oauthclient/apps'));
    }

    /**
     * Get a random state value
     * @param int $length
     * @return string
     * @throws Exception
     */
    protected function getRandomState($length = 32)
    {
        // Converting bytes to hex will always double length. Hence, we can reduce
        // the amount of bytes by half to produce the correct length.
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Will refresh a token by its ID
     * @param $id
     * @return Response|\yii\console\Response
     * @throws IdentityProviderException
     * @throws Exception
     */
    public function actionRefresh($id)
    {
        $token = Plugin::$plugin->tokens->getTokenById($id);
        if (!$token) {
            return Craft::$app->response->setStatusCode(404, 'Token not found');
        }

        $app = $token->getApp();
        $this->requirePermission('oauthclient-login:' . $app->uid);

        $refreshed = Plugin::$plugin->credentials->refreshToken($token);
        if ($refreshed) {
            $app = $token->getApp();
            return Craft::$app->response->redirect($app->getCpEditUrl());
        }

        throw new Exception('Failed to refresh token');
    }
}
