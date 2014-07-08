<?php

namespace Hamlet\GoogleDrive;

use Google_Client;

class GoogleDriveClientFactory
{
    /**
     * Get client by client id and client secret
     *
     * @param string $clientId
     * @param string $clientSecret
     * @param string|null $accessToken
     *
     * @return \Google_Client
     */
    public static function getClient($clientId, $clientSecret, $accessToken = null)
    {
        $client = new Google_Client();

        global $apiConfig;
        $apiConfig['use_objects'] = true;

        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri('urn:ietf:wg:oauth:2.0:oob');
        $client->setScopes(array('https://www.googleapis.com/auth/drive'));
        $client->setAccessType('offline');

        if ($accessToken != null) {
            $client->setAccessToken($accessToken);
        }

        return $client;
    }
}