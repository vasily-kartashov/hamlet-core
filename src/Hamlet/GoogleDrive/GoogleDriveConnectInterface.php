<?php

namespace Hamlet\GoogleDrive;

interface GoogleDriveConnectInterface
{
    /**
     * Get access token
     *
     * @return string
     */
    public function getAccessToken();

    /**
     * Get the ids of image folders
     *
     * @return string[]
     */
    public function getImageFolderIds();

    /**
     * Get the ids of folders with context matrices
     *
     * @return string[]
     */
    public function getContentMatrixFolderIds();

    /**
     * Get the target path for imported images
     *
     * @return string
     */
    public function getImagesDirectoryPath();

    /**
     * Get the target path for imported content matrices
     *
     * @return string
     */
    public function getContentMatricesDirectoryPath();
}