<?php

namespace Hamlet\GoogleDrive;

use Google_Client;
use Google_Service_Drive;

class GoogleDriveService
{
    /** @var \Google_Client */
    private $client;

    public function __construct(Google_Client $client)
    {
        $this->client = $client;
    }

    /**
     * Get files from google drive folder
     *
     * @param string $folderId
     *
     * @return \Google_Service_Drive_DriveFile[]
     */
    public function getFiles($folderId)
    {
        assert(is_string($folderId));

        $service = new Google_Service_Drive($this->client);

        $result = array();
        $pageToken = null;
        do {
            $list = $service->files->listFiles(array(
                'q' => "'{$folderId}' in parents",
                'maxResults' => 1000,
            ));
            foreach ($list->getItems() as $file) {
                /** @var $file \Google_Service_Drive_DriveFile */
                /** @var $labels \Google_Service_Drive_DriveFileLabels */
                $labels = $file->getLabels();

                if (!$labels-> getTrashed()) {
                    $result[$file->getTitle()] = $file;
                }
            }
            $pageToken = $list->getNextPageToken();
        } while ($pageToken);

        return $result;
    }
}