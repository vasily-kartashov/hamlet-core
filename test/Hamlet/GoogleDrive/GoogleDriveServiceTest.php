<?php

namespace Hamlet\GoogleDrive;

use UnitTestCase;

class GoogleDriveServiceTest extends UnitTestCase
{
    private $client;
    private $testFolderId = '0B_2kJEFibcfiNUI4TVFXSXA4OTg';

    public function __construct()
    {
        $path = realpath(__DIR__ . '/../../../.credentials/google.json');
        $credentials = json_decode(file_get_contents($path));

        $factory = new GoogleDriveClientFactory();
        $clientId = $credentials->hamletCore->clientId;
        $clientSecret = $credentials->hamletCore->clientSecret;
        $accessToken = $credentials->hamletCore->accessToken;

        $this->client = $factory->getClient($clientId, $clientSecret, $accessToken);
    }

    public function testGoogleDriveFileListing()
    {
        $service = new GoogleDriveService($this->client);
        $files = $service->getFiles($this->testFolderId);
        $this->assertTrue(count($files) > 0);
    }

    public function testReadingBinaryContent()
    {
        $service = new GoogleDriveService($this->client);
        $files = $service->getFiles($this->testFolderId);
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
        $files = $service->getFiles($this->testFolderId);
        foreach ($files as $file) {
            if ($file->getTitle() == 'spreadsheet') {
                $content = $service->readSpreadsheetContent($file);
                $this->assertTrue(isset($content['a']));
                $this->assertTrue(isset($content['b']));
            }
        }
    }
}