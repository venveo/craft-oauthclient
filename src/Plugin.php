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
use craft\base\Plugin as BasePlugin;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\log\FileTarget;
use craft\web\UrlManager;
use venveo\oauthclient\services\Apps as AppsService;
use venveo\oauthclient\services\Credentials as CredentialsService;
use venveo\oauthclient\services\Providers as ProvidersService;
use venveo\oauthclient\services\Tokens as TokensService;
use yii\base\Event;

/**
 * Class Plugin
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
class Plugin extends BasePlugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var Plugin
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $schemaVersion = '1.0.0';
    public $hasCpSettings = true;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        $this->_registerLogger();
        $this->_setComponents();
        $this->_registerCpRoutes();
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

    // Private Methods
    // =========================================================================

    /**
     *
     */
    private function _registerLogger() {
            Craft::getLogger()->dispatcher->targets[] = new FileTarget([
                'logFile' => Craft::getAlias('@storage/logs/oauthclient.log'),
                'categories' => ['venveo\oauthclient\*'],
            ]);
    }

    /**
     *
     */
    private function _setComponents()
    {
        $this->setComponents([
            'apps' => AppsService::class,
            'providers' => ProvidersService::class,
            'tokens' => TokensService::class,
            'credentials' => CredentialsService::class,
        ]);

    }

    /**
     * Adds the event handler for registering CP routes
     */
    private function _registerCpRoutes()
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function (RegisterUrlRulesEvent $event) {
            $event->rules = array_merge($event->rules, [
                'oauthclient' => 'oauthclient/apps/index',
                'oauthclient/apps' => 'oauthclient/apps/index',
                'oauthclient/apps/new' => 'oauthclient/apps/edit',
                'oauthclient/apps/<handle:{handle}>' => 'oauthclient/apps/edit',
                'oauthclient/authorize/refresh/<id:\d+>' => 'oauthclient/authorize/refresh',
                'oauthclient/authorize/<handle:{handle}>' => 'oauthclient/authorize/authorize-app',
            ]);
        });
    }
}
