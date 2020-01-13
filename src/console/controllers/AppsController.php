<?php
/**
 *  OAuth 2.0 Client plugin for Craft CMS 3
 * @link      https://www.venveo.com
 * @copyright Copyright (c) 2018-2019 Venveo
 */

namespace venveo\oauthclient\console\controllers;

use craft\console\Controller;
use venveo\oauthclient\models\Token;
use venveo\oauthclient\Plugin;

class AppsController extends Controller
{

    /**
     * Refresh all tokens for a given app handle
     * @param $appHandle
     * @return int
     */
    public function actionRefreshTokens($appHandle)
    {
        $credentialService = Plugin::$plugin->credentials;
        $appService = Plugin::$plugin->apps;
        if (!$app = $appService->getAppByHandle($appHandle)) {
            $this->stderr('No app found with that handle' . PHP_EOL);
            return 1;
        }

        $tokens = $app->getAllTokens();
        $total = count($tokens);
        if (!$total) {
            $this->stdout('No tokens exist for that app' . PHP_EOL);
            return 0;
        }
        $progress = 0;
        $hadErrors = false;
        /** @var Token $token */
        foreach ($tokens as $token) {
            ++$progress;
            $prefix = "($progress/$total)";
            $refreshed = $credentialService->refreshToken($token);
            if (!$refreshed) {
                $this->stderr($prefix . ' Failed to refresh token ID: ' . $token->id . PHP_EOL);
                $hadErrors = true;
            } else {
                $this->stdout($prefix . ' Refreshed token ID: ' . $token->id . PHP_EOL);
            }
        }

        return $hadErrors ? 1 : 0;
    }
}