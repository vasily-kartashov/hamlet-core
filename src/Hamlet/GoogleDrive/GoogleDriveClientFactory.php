<?php

namespace Hamlet\GoogleDrive;

use Exception;
use Google_Client;

class GoogleDriveClientFactory
{
    const PROFILES_PATH = '~/.hamlet/google-profiles.json';

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
        $path = GoogleDriveClientFactory::PROFILES_PATH;
        $fullPath = realpath($path);
        if (!$fullPath) {
            throw new Exception("The configuration file '{$path}' is missing");
        }
        $settings = json_decode(file_get_contents($fullPath));
        if (!isset($settings->{$profileName})) {
            throw new Exception("Cannot find profile '{$profileName}'");
        }
        $section = $settings->{$profileName};
        $clientId = $section->clientId;
        $clientSecret = $section->clientSecret;
        $accessToken = $section->accessToken;

        return $this->getClient($clientId, $clientSecret, $accessToken);
    }
}