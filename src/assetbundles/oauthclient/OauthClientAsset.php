<?php
/**
 * OAuth 2.0 Client plugin for Craft CMS 3.x
 *
 * Simple OAuth 2.0 client
 *
 * @link      https://venveo.com
 * @copyright Copyright (c) 2018 Venveo
 */

namespace venveo\oauthclient\assetbundles\OauthClient;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * @author    Venveo
 * @package   Oauth20Client
 * @since     1.0.0
 */
class OauthClientAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = "@venveo/oauthclient/assetbundles/oauthclient/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/OauthClient.js',
        ];

        $this->css = [
            'css/OauthClient.css',
        ];

        parent::init();
    }
}
