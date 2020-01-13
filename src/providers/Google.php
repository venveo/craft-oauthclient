<?php
/**
 * OAuth 2.0 Client plugin for Craft CMS 3.x
 *
 * Simple OAuth 2.0 client
 *
 * @link      https://venveo.com
 * @copyright Copyright (c) 2019 Venveo
 */

namespace venveo\oauthclient\providers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use League\OAuth2\Client\Provider\Google as GoogleProvider;
use venveo\oauthclient\base\Provider;
use venveo\oauthclient\base\ValidatesTokens;
use venveo\oauthclient\models\Token;

/**
 * @author    Venveo
 * @package   OauthClient
 * @since     1.0.0
 */
class Google extends Provider implements ValidatesTokens
{
    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return 'Google';
    }

    /**
     * @inheritDoc
     */
    public static function getProviderClass(): string
    {
        return GoogleProvider::class;
    }

    /**
     * @inheritDoc
     */
    public static function checkToken(Token $token): bool
    {
        $url = 'https://www.googleapis.com/oauth2/v1/tokeninfo?access_token=' . $token->accessToken;
        $client = new Client();
        try {
            $resp = $client->request('GET', $url);
            $status = $resp->getStatusCode();
            return ($status === 200);
        } catch (ClientException $e) {
            return false;
        }
    }
}
