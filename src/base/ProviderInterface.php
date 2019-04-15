<?php

namespace venveo\oauthclient\base;
use League\OAuth2\Client\Provider\AbstractProvider;
use venveo\oauthclient\models\App as AppModel;

interface ProviderInterface
{
    public static function displayName():string;

    /**
     * @return AbstractProvider
     */
    public function getConfiguredProvider();

    public function getAuthorizeURL($options):string;

    public function getState():?string;

    public function getAccessToken($grant, $options = []);
}
