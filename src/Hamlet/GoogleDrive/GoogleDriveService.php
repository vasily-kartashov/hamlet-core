<?php

namespace Hamlet\GoogleDrive;

use Exception;
use Google_Client;
use Google_Http_Request;
use Google_Service_Drive;
use Google_Service_Drive_DriveFile;
use PHPExcel_Reader_Excel2007;

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

                if (!$labels->getTrashed()) {
                    $result[] = $file;
                }
            }
            $pageToken = $list->getNextPageToken();
        } while ($pageToken);

        return $result;
    }

    private function downloadToTempFile(Google_Service_Drive_DriveFile $file, $downloadUrl)
    {
        $tempPath = sys_get_temp_dir() . '/' . md5($downloadUrl);
        if (!file_exists($tempPath) or $file->getMd5Checksum() != md5_file($tempPath)) {
            $request = new Google_Http_Request($downloadUrl, 'GET', null, null);
            $signedRequest = $this->client->getAuth()->sign($request);
            /** @var \Google_Http_Request $updatedRequest */
            $updatedRequest = $this->client->getIo()->makeRequest($signedRequest);
            if ($updatedRequest->getResponseHttpCode() == 200) {
                $content = $updatedRequest->getResponseBody();
                file_put_contents($tempPath, $content);
            } else {
                throw new Exception('Cannot download the file ' . $file->getId() . ' (' . $file->getTitle() . ')');
            }
        }
        return $tempPath;
    }

    /**
     * Download binary file
     *
     * @param \Google_Service_Drive_DriveFile $file
     *
     * @return string
     */
    public function readBinaryContent(Google_Service_Drive_DriveFile $file)
    {
        $tempFile = $this->downloadToTempFile($file, $file->getDownloadUrl());
        return file_get_contents($tempFile);
    }

    /**
     * Download and parse spreadsheet
     *
     * @param \Google_Service_Drive_DriveFile $file
     *
     * @return array
     */
    public function readSpreadsheetContent(Google_Service_Drive_DriveFile $file)
    {
        $exportLinks = $file->getExportLinks();
        $downloadUrl = $exportLinks['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        $tempFile = $this->downloadToTempFile($file, $downloadUrl);

        $reader = new PHPExcel_Reader_Excel2007();
        $excel = $reader->load($tempFile);

        $result = array();
        $sheets = $excel->getAllSheets();
        foreach ($sheets as $sheet) {
            $result[$sheet->getTitle()] = $sheet->toArray(null, true, true, true);
        }
        return $result;
    }
}