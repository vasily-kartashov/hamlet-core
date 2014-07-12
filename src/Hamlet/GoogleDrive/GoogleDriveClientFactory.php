<?php

namespace Hamlet\GoogleDrive;

use Google_Client;
use Hamlet\Profile\ProfileCollection;

class GoogleDriveClientFactory
{
    const SETTINGS_KEY = 'googleApp';

    /**
     * Get client by client id and client secret
     *
     * @param string $clientId
     * @param string $clientSecret
     * @param mixed $accessToken
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
            $client->setAccessToken(json_encode($accessToken));
        }

        return $client;
    }

    /**
     * Get client for specified profile
     *
     * @param string $profileName
     *
     * @throws \Exception
     *
     * @return \Google_Client
     */
    public function getClientForProfile($profileName)
    {
        $collection = new ProfileCollection();
        $settings = $collection->getProfile($profileName)->{GoogleDriveClientFactory::SETTINGS_KEY};

        $clientId = $settings->clientId;
        $clientSecret = $settings->clientSecret;
        $accessToken = $settings->accessToken;

        return $this->getClient($clientId, $clientSecret, $accessToken);
    }
}