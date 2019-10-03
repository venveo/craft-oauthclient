<?php
/**
 *  OAuth 2.0 Client plugin for Craft CMS 3
 * @link      https://www.venveo.com
 * @copyright Copyright (c) 2018-2019 Venveo
 */

namespace venveo\oauthclient\variables;

use venveo\oauthclient\models\App;
use venveo\oauthclient\Plugin;

class OAuthVariable
{

    /**
     * Gets an OAuth app by its handle
     * @param $handle
     * @return App|null
     */
    public function getAppByHandle($handle)
    {
        return Plugin::$plugin->apps->getAppByHandle($handle);
    }
}