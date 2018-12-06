<?php

namespace venveo\oauthclient\base;

use craft\base\Component;
use venveo\oauthclient\models\App as AppModel;

abstract class Provider extends Component implements ProviderInterface
{
    private $app;
    protected $configuredProvider;

    public function setApp(AppModel $app)
    {
        $this->app = $app;
    }

    public function getApp(): AppModel
    {
        return $this->app;
    }

    public function getAuthorizeURL($options = []): string
    {
        $provider = $this->getConfiguredProvider();
        return $provider->getAuthorizationUrl($options);
    }

    public function getState():?string {
        return \Craft::$app->request->getCsrfToken();
    }

    public function getAccessToken($grant, $options = []) {
        return $this->getConfiguredProvider()->getAccessToken($grant, $options);
    }
}
