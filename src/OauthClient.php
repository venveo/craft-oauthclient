<?php
/**
 * OAuth 2.0 Client plugin for Craft CMS 3.x
 *
 * Simple OAuth 2.0 client
 *
 * @link      https://venveo.com
 * @copyright Copyright (c) 2018 Venveo
 */

namespace venveo\oauthclient;

use Craft;
use craft\base\Plugin;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\web\UrlManager;
use venveo\oauthclient\services\Apps as AppsService;
use venveo\oauthclient\services\Credentials;
use venveo\oauthclient\services\Credentials as CredentialsService;
use venveo\oauthclient\services\Providers as ProvidersService;
use venveo\oauthclient\services\Tokens as TokensService;
use yii\base\Event;

/**
 * Class OauthClient
 *
 * @author    Venveo
 * @package   OauthClient
 * @since     1.0.0
 *
 *
 * @property mixed $settingsResponse
 * @property AppsService $apps
 * @property ProvidersService $providers
 * @property TokensService $tokens
 * @property CredentialsService $credentials
 */
class OauthClient extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var OauthClient
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $schemaVersion = '1.0.0';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules['oauthclient/apps'] = 'oauthclient/apps/index';
            $event->rules['oauthclient/apps/new'] = 'oauthclient/apps/edit';
            $event->rules['oauthclient/apps/<id:\d+>'] = 'oauthclient/apps/edit';
            $event->rules['oauthclient/authorize/<handle:{handle}>'] = 'oauthclient/authorize/authorize-app';
        }
        );

        $this->setComponents([
            'apps' => AppsService::class,
            'providers' => ProvidersService::class,
            'tokens' => TokensService::class,
            'credentials' => CredentialsService::class,
        ]);
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getSettingsResponse()
    {
        return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('oauthclient/apps'));
    }
}
