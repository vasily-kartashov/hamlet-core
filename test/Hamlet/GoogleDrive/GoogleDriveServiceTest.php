<?php

namespace Hamlet\GoogleDrive;

use UnitTestCase;

class GoogleDriveServiceTest extends UnitTestCase
{
    public function testGoogleDriveFileListing()
    {
        $credentialsDirectoryPath = realpath(__DIR__ . '/../../../.credentials');

        $settings = parse_ini_file($credentialsDirectoryPath . '/settings');
        $token = file_get_contents($credentialsDirectoryPath . '/token.json');

        $factory = new GoogleDriveClientFactory();
        $client = $factory->getClient($settings['google.client-id'], $settings['google.client-secret'], $token);

        $service = new GoogleDriveService($client);
        $files = $service->getFiles($settings['google.test-folder']);

        $this->assertTrue(count($files) > 0);
    }
}