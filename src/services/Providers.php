<?php
/**
 * OAuth 2.0 Client plugin for Craft CMS 3.x
 *
 * Simple OAuth 2.0 client
 *
 * @link      https://venveo.com
 * @copyright Copyright (c) 2018 Venveo
 */

namespace venveo\oauthclient\services;

use craft\base\Component;
use craft\errors\MissingComponentException;
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\Component as ComponentHelper;
use venveo\oauthclient\base\Provider;
use venveo\oauthclient\base\ProviderInterface;
use venveo\oauthclient\providers\Facebook;
use venveo\oauthclient\providers\GitHub;
use venveo\oauthclient\providers\Google;
use venveo\oauthclient\providers\MissingProvider;
use yii\base\InvalidConfigException;

/**
 * @author    Venveo
 * @package   OauthClient
 * @since     1.0.0
 *
 *
 * @property array $allProviderTypes
 */
class Providers extends Component
{
    const EVENT_REGISTER_PROVIDER_TYPES = 'EVENT_REGISTER_GATEWAY_TYPES';

    /**
     * Get all of the registered provider types. This is a great place to register your custom providers!
     * @return array
     */
    public function getAllProviderTypes(): array
    {
        $providerTypes = [];
        $event = new RegisterComponentTypesEvent([
            'types' => $providerTypes
        ]);

        if ($this->hasEventHandlers(self::EVENT_REGISTER_PROVIDER_TYPES)) {
            $this->trigger(self::EVENT_REGISTER_PROVIDER_TYPES, $event);
        }

        return $event->types;
    }

    /**
     * Creates a Provider with a given config
     *
     * @param mixed $config The providers’s class name, or its config, with a `type` value and optionally a `settings` value
     * @return Provider The provider
     * @throws InvalidConfigException
     */
    public function createProvider($config): Provider
    {
        if (is_string($config)) {
            $config = ['type' => $config];
        }

        try {

            if ($config['type'] == MissingProvider::class) {
                throw new MissingComponentException('Missing Provider Class.');
            }

            /** @var Provider $provider */
            $provider = ComponentHelper::createComponent($config, ProviderInterface::class);
        } catch (MissingComponentException $e) {
            $config['errorMessage'] = $e->getMessage();
            $config['expectedType'] = $config['type'];
            unset($config['type']);

            $provider = new MissingProvider($config);
        }

        return $provider;
    }
}
