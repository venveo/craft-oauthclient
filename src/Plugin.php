<?php
/**
 * OAuth 2.0 Client plugin for Craft CMS 3
 * @link      https://www.venveo.com
 * @copyright Copyright (c) 2018-2019 Venveo
 */

namespace venveo\oauthclient;

use Craft;
use craft\base\Plugin as BasePlugin;
use craft\events\RebuildConfigEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\helpers\UrlHelper;
use craft\log\MonologTarget;
use craft\services\ProjectConfig;
use craft\services\UserPermissions;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use Monolog\Formatter\LineFormatter;
use Psr\Log\LogLevel;
use venveo\oauthclient\services\Apps as AppsService;
use venveo\oauthclient\services\Credentials as CredentialsService;
use venveo\oauthclient\services\Providers;
use venveo\oauthclient\services\Providers as ProvidersService;
use venveo\oauthclient\services\Tokens as TokensService;
use venveo\oauthclient\variables\OAuthVariable;
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
    public const HANDLE = 'oauthclient';

    public static string $PROJECT_CONFIG_KEY = 'oauthClient';

    public string $schemaVersion = '2.1.3';
    public bool $hasCpSettings = true;



    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (Craft::$app->request->getIsConsoleRequest()) {
            $this->controllerNamespace = 'venveo\oauthclient\console\controllers';
        }

        $this->_registerLogger();
        $this->_registerCpRoutes();
        $this->_registerVariables();
        $this->_registerProjectConfig();
        $this->_registerPermissions();
    }

    // Protected Methods
    // =========================================================================

    /**
     * Register a custom logger
     */
    private function _registerLogger()
    {
        Craft::getLogger()->dispatcher->targets[] = new MonologTarget([
            'name' => 'oauthclient',
            'categories' => ['venveo\oauthcleint\*'],
            'level' => LogLevel::INFO,
            'logContext' => false,
            'allowLineBreaks' => false,
            'formatter' => new LineFormatter(
                format: "[%datetime%] %message%\n",
                dateFormat: 'Y-m-d H:i:s',
            ),
        ]);
    }


    /**
     * @inheritdoc
     */
    public static function config(): array
    {
        return [
            'components' => [
                'apps' => ['class' => AppsService::class],
                'providers' => ['class' => ProvidersService::class],
                'tokens' => ['class' => TokensService::class],
                'credentials' => ['class' => CredentialsService::class]
            ],
        ];
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
                'oauthclient/apps/delete' => 'oauthclient/apps/delete',

                'oauth/authorize/refresh/<id:\d+>' => 'oauthclient/authorize/refresh',
                'oauth/authorize/<handle:{handle}>' => 'oauthclient/authorize/authorize-app',
            ]);
        });
    }

    /**
     * Set our Twig variable
     */
    private function _registerVariables()
    {
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                $variable = $event->sender;
                $variable->set('oauth', OAuthVariable::class);
            }
        );
    }

    /**
     *  Register project config handlers
     */
    private function _registerProjectConfig()
    {
        Craft::$app->projectConfig
            ->onAdd(self::$PROJECT_CONFIG_KEY . '.apps.{uid}', [$this->apps, 'handleUpdatedApp'])
            ->onUpdate(self::$PROJECT_CONFIG_KEY . '.apps.{uid}', [$this->apps, 'handleUpdatedApp'])
            ->onRemove(self::$PROJECT_CONFIG_KEY . '.apps.{uid}', [$this->apps, 'handleRemovedApp']);

        Event::on(ProjectConfig::class, ProjectConfig::EVENT_REBUILD, function (RebuildConfigEvent $e) {
            $this->_handleProjectConfigRebuild($e);
        });
    }

    /**
     * Handle project config rebuilding
     * @param RebuildConfigEvent $e
     */
    private function _handleProjectConfigRebuild(RebuildConfigEvent $e)
    {
        $appData = [];
        $apps = $this->apps->getAllApps();
        foreach ($apps as $app) {
            $appData[$app->uid] = [
                'name' => $app->name,
                'handle' => $app->handle,
                'provider' => $app->provider,
                'clientId' => $app->clientId,
                'clientSecret' => $app->clientSecret,
                'urlAuthorize' => $app->urlAuthorize,
                'scopes' => $app->scopes
            ];
        }

        $e->config[self::$PROJECT_CONFIG_KEY]['apps'] = $appData;
    }

    private function _registerPermissions()
    {
        Event::on(UserPermissions::class, UserPermissions::EVENT_REGISTER_PERMISSIONS, function (RegisterUserPermissionsEvent $event) {
            $apps = $this->apps->getAllApps();
            $loginPermissions = [];
            foreach ($apps as $app) {
                $suffix = ':' . $app->uid;
                $loginPermissions['oauthclient-login' . $suffix] = ['label' => self::t('Login to “{name}” ({handle}) app', ['name' => $app->name, 'handle' => $app->handle])];
            }
            $event->permissions[] = [
                'heading' => self::t('OAuth Client'),
                'permissions' => [
                    'oauthclient-login' => [
                        'label' => self::t('Login to Apps'), 'nested' => $loginPermissions
                    ]
                ],
            ];
        });
    }

    /**
     * @see Craft::t()
     */
    public static function t($message, $params = [], $language = null)
    {
        return Craft::t(self::HANDLE, $message, $params, $language);
    }

    /**
     * @inheritdoc
     */
    public function getSettingsResponse(): mixed
    {
        return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('oauthclient/apps'));
    }
}
