<?php

namespace Hamlet\GoogleDrive;

use UnitTestCase;

class GoogleDriveServiceTest extends UnitTestCase
{
    private $client;
    private $settings;

    public function __construct()
    {
        $credentialsDirectoryPath = realpath(__DIR__ . '/../../../.credentials');

        $this->settings = parse_ini_file($credentialsDirectoryPath . '/settings');
        $token = file_get_contents($credentialsDirectoryPath . '/token.json');

        $factory = new GoogleDriveClientFactory();
        $clientId = $this->settings['google.client-id'];
        $clientSecret = $this->settings['google.client-secret'];

        $this->client = $factory->getClient($clientId, $clientSecret, $token);
    }

    public function testGoogleDriveFileListing()
    {
        $service = new GoogleDriveService($this->client);
        $testFolderId = $this->settings['google.test-folder'];
        $files = $service->getFiles($testFolderId);
        $this->assertTrue(count($files) > 0);
    }

    public function testReadingBinaryContent()
    {
        $service = new GoogleDriveService($this->client);
        $testFolderId = $this->settings['google.test-folder'];
        $files = $service->getFiles($testFolderId);
        foreach ($files as $file) {
            if ($file->getTitle() == 'image.jpg') {
                $content = $service->readBinaryContent($file);
                $this->assertTrue(strlen($content) > 0);
            }
        }
    }

    public function testReadingSpreadsheetContent()
    {
        $service = new GoogleDriveService($this->client);
        $testFolderId = $this->settings['google.test-folder'];
        $files = $service->getFiles($testFolderId);
        foreach ($files as $file) {
            if ($file->getTitle() == 'spreadsheet') {
                $content = $service->readSpreadsheetContent($file);
                $this->assertTrue(isset($content['a']));
                $this->assertTrue(isset($content['b']));
            }
        }
    }
}