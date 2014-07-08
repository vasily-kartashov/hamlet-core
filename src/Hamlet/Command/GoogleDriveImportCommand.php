<?php

namespace Hamlet\Command;

use Hamlet\GoogleDrive\GoogleDriveConnectInterface;
use Symfony\Component\Console\Command\Command;

class GoogleDriveImportCommand extends Command
{
    private $googleDriveConnect;

    public function __construct(GoogleDriveConnectInterface $googleDriveConnect)
    {
        $this->googleDriveConnect = $googleDriveConnect;
    }
}