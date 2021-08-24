<?php
/**
 *  OAuth 2.0 Client plugin for Craft CMS 3
 * @link      https://www.venveo.com
 * @copyright Copyright (c) 2018-2020 Venveo
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
use venveo\oauthclient\events\AuthorizationEvent;
use venveo\oauthclient\models\App as AppModel;
use venveo\oauthclient\Plugin;
use yii\web\HttpException;

/**
 * @author    Venveo
 * @package   Oauth20Client
 * @since     1.0.0
 */
class AuthorizeController extends Controller
{
    const EVENT_BEFORE_AUTHENTICATE = 'EVENT_BEFORE_AUTHENTICATE';
    const EVENT_AFTER_AUTHENTICATE = 'EVENT_AFTER_AUTHENTICATE';

    const STATE_SESSION_KEY = 'oauth2state';
    const CONTEXT_SESSION_KEY = 'OAUTH_CONTEXT';
    const REDIRECT_URL_SESSION_KEY = 'OAUTH_REDIRECT_URL';

    /**
     * Handles the actual OAuth process
     *
     * @param string $handle
     * @return Response
     * @throws IdentityProviderException
     * @throws ReflectionException
     * @throws Exception
     */
    public function actionAuthorizeApp($handle): Response
    {
        // These are things that would be returned from our OAuth provider
        $error = Craft::$app->request->getParam('error');
        $code = Craft::$app->request->getParam('code');
        $state = Craft::$app->request->getParam('state');

        $event = new AuthorizationEvent();

        $returnUrl = Craft::$app->request->getQueryParam('returnUrl');
        if ($returnUrl) {
            $returnUrl = Craft::$app->security->validateData($returnUrl);
            if (!$returnUrl) {
                throw new HttpException(400, 'Security hash not valid');
            }
            $event->returnUrl = $returnUrl;
        }

        // If any of those items are set, we'll assume we're getting a callback from the provider
        $callbackMode = false;
        if ($state || $error || $code) {
            $callbackMode = true;
        }

        // We can either have a context in the params or in the session
        $event->context = Craft::$app->request->getParam('context');
        if (Craft::$app->session->get(self::CONTEXT_SESSION_KEY)) {
            $event->context = Craft::$app->session->get(self::CONTEXT_SESSION_KEY);
            Craft::$app->session->remove(self::CONTEXT_SESSION_KEY);
        }

        // If it's a form submission, the form may have a redirect URI for after authenticating
        if (Craft::$app->request->isPost && $redirectUrl = Craft::$app->getRequest()->getValidatedBodyParam('redirect')) {
            $event->returnUrl = $redirectUrl;
        }

        // We're coming back from our OAuth provider and we have a redirect url set in our session
        if ($sessionRedirectUrl = Craft::$app->session->get(self::REDIRECT_URL_SESSION_KEY)) {
            $event->returnUrl = $sessionRedirectUrl;
            Craft::$app->session->remove(self::REDIRECT_URL_SESSION_KEY);
        }

        // Give the event a chance to override the app handle
        $event->appHandle = $handle;

        if (!$callbackMode) {
            $this->trigger(self::EVENT_BEFORE_AUTHENTICATE, $event);
        }

        if (!$callbackMode) {
            // We need to store the redirect URL in the session since the user is leaving the website for a moment for OAuth
            // Note: We're not using the absolute URL here because it will be set to this controller's URL
            $returnUrl = $event->returnUrl ?? UrlHelper::cpUrl('oauthclient/apps');
            $event->returnUrl = $returnUrl;
            Craft::$app->session->set(self::REDIRECT_URL_SESSION_KEY, $returnUrl);
        }

        $app = Plugin::$plugin->apps->getAppByHandle($event->appHandle);
        if (!$app instanceof AppModel) {
            Craft::$app->response->setStatusCode(404, 'App handle does not exist');
            return Craft::$app->response;
        }

        $this->requirePermission('oauthclient-login:' . $app->uid);

        /** @var Provider $provider */
        $provider = $app->getProviderInstance();

        // OAuth provider sent back an error
        if (!empty($error)) {
            Craft::error($error, __METHOD__);
            Craft::$app->session->remove(self::STATE_SESSION_KEY);
            Craft::$app->session->setError(Craft::t('oauthclient', 'Failed to authorize app: ' . $error));
            return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl($event->returnUrl));
        }

        if (empty($code)) {
            $state = $this->getRandomState();
            Craft::$app->session->set(self::STATE_SESSION_KEY, $state);
            Craft::$app->session->set(self::CONTEXT_SESSION_KEY, $event->context);
            $url = Plugin::$plugin->apps->getAuthorizationUrlForApp($app, $state, $event->context);
            return Craft::$app->response->redirect($url);
        }

        // At this point, we should definitely be in callback mode - so we'll

        if (empty($state) || Craft::$app->session->get(self::STATE_SESSION_KEY) !== $state) {
            Craft::$app->session->setError(Craft::t('oauthclient', 'Invalid OAuth 2 State'));
            Craft::$app->session->remove(self::STATE_SESSION_KEY);
            return Craft::$app->response->redirect($event->returnUrl);
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
                $event->token = $token;
                Craft::$app->session->setFlash(Craft::t('oauthclient', 'Connected via ' . $app->name));
            } else {
                Craft::$app->session->setError(Craft::t('oauthclient', 'Failed to save token'));
            }
        } catch (Exception $e) {
            Craft::error($e->getTraceAsString(), __METHOD__);
            Craft::$app->session->setError(Craft::t('oauthclient', 'Something went wrong: ' . $e->getMessage()));
        }

        $this->trigger(self::EVENT_AFTER_AUTHENTICATE, $event);

        return Craft::$app->getResponse()->redirect(UrlHelper::url($event->returnUrl ?? ''));
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
