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

use craft\helpers\UrlHelper;
use craft\web\Response;
use venveo\oauthclient\base\Provider;
use venveo\oauthclient\models\Token as TokenModel;
use venveo\oauthclient\Plugin;
use venveo\oauthclient\models\App as AppModel;

use Craft;
use craft\web\Controller;

/**
 * @author    Venveo
 * @package   Oauth20Client
 * @since     1.0.0
 */
class AuthorizeController extends Controller
{
    public const STATE_SESSION_KEY = 'oauth2state';

    /**
     * Handles the actual OAuth process
     * @param string $handle
     * @return Response
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     * @throws \craft\errors\SiteNotFoundException
     */
    public function actionAuthorizeApp($handle): Response
    {
        /** @var  $app */
        $app = Plugin::$plugin->apps->getAppByHandle($handle);
        if (!$app instanceof AppModel) {
            \Craft::$app->response->setStatusCode(404, 'App handle does not exist');
            return null;
        }

        $error = \Craft::$app->request->getParam('error');
        $code = \Craft::$app->request->getParam('code');
        $state = \Craft::$app->request->getParam('state');

        /** @var Provider $provider */
        $provider = $app->getProviderInstance();

        // OAuth provider sent back an error
        if (!empty($error)) {
            \Craft::$app->session->remove(self::STATE_SESSION_KEY);
            \Craft::$app->session->setError(Craft::t('oauthclient', 'Failed to authorize app: '. $error));
            return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('oauthclient/apps'));
        }

        // Begin auth process
        if (empty($code)) {
            $state = $this->getRandomState();
            $url = $provider->getAuthorizeURL(['state' => $state]);
            \Craft::$app->session->set(self::STATE_SESSION_KEY, $state);
            return \Craft::$app->response->redirect($url);

        // Invalid state
        } elseif(empty($state) || \Craft::$app->session->get(self::STATE_SESSION_KEY) !== $state) {

            \Craft::$app->session->setError(Craft::t('oauthclient', 'Invalid OAuth 2 State'));
            \Craft::$app->session->remove(self::STATE_SESSION_KEY);
            return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('oauthclient/apps'));
        } else {
            $tokenResponse = $provider->getConfiguredProvider()->getAccessToken('authorization_code', [
                'code' => $code
            ]);

            // We need to save the token
            $token = TokenModel::fromLeagueToken($tokenResponse);
            $token->appId = $app->id;
            $token->userId = \Craft::$app->user->getId();

            try {
                $saved = Plugin::$plugin->tokens->saveToken($token);
                if($saved) {
                    \Craft::$app->session->setFlash(Craft::t('oauthclient', 'Connected via '.$app->name));
                } else {
                    \Craft::$app->session->setError(Craft::t('oauthclient', 'Failed to save token'));
//                    var_dump($token->getErrors());
//                    die();
                }
            } catch (\Exception $e) {
                \Craft::$app->session->setError(Craft::t('oauthclient', 'Something went wrong: '. $e->getMessage()));
            }
            return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('oauthclient/apps'));
        }
    }

    public function actionRefresh($id) {
        $token = Plugin::$plugin->tokens->getTokenById($id);
        if (!$token) {
            \Craft::$app->response->setStatusCode(404, "Token not found");
        }

        $refreshed = Plugin::$plugin->credentials->refreshToken($token);
        if ($refreshed) {
            $app = $token->getApp();
            return \Craft::$app->response->redirect($app->getCpEditUrl());
        }
        throw new \Exception('Failed to refresh token');
    }

    protected function getRandomState($length = 32)
    {
        // Converting bytes to hex will always double length. Hence, we can reduce
        // the amount of bytes by half to produce the correct length.
        return bin2hex(random_bytes($length / 2));
    }
}
