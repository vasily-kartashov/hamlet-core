<?php

namespace Hamlet\Command;

use Exception;
use Hamlet\GoogleDrive\GoogleDriveClientFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GoogleDriveAuthenticateCommand extends Command
{
    private $currentDirectoryPath;

    public function __construct($currentDirectoryPath)
    {
        parent::__construct();
        assert(is_string($currentDirectoryPath) && is_dir($currentDirectoryPath));
        $this->currentDirectoryPath = $currentDirectoryPath;
    }

    protected function configure()
    {
        $this
            ->setName('google-auth')
            ->setDescription('Authenticate application for Google drive')
            ->addArgument('profile', InputArgument::REQUIRED, 'Profile name')
            ->addOption('target', null, InputOption::VALUE_REQUIRED, 'Target path');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $targetPath = $input->getOption('target');
        if ($targetPath == null) {
            $targetPath = $this->currentDirectoryPath . '/.credentials/google.json';
        }
        $targetDirectoryPath = dirname($targetPath);
        if (!file_exists($targetDirectoryPath)) {
            if (!mkdir(dirname($targetDirectoryPath), 700, true)) {
                throw new Exception("Cannot create directory '{$targetDirectoryPath}'");
            }
        }
        if (file_exists($targetPath)) {
            $settings = json_decode(file_get_contents($targetPath));
        } else {
            $settings = [];
        }

        $factory = new GoogleDriveClientFactory();
        print('Enter Google client ID: ');
        $clientId = trim(fgets(STDIN));
        print('Enter Google client secret: ');
        $clientSecret = trim(fgets(STDIN));

        $client = $factory->getClient($clientId, $clientSecret);

        $url = $client->createAuthUrl();
        print('Visit the following URL and enter the code' . PHP_EOL);
        print($url . PHP_EOL);
        print('Please enter the code: ');
        $authCode = trim(fgets(STDIN));

        $profile = $input->getArgument('profile');
        $settings->{$profile} = [
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'accessToken' => json_decode($client->authenticate($authCode)),
        ];
        file_put_contents($targetPath, json_encode($settings));
    }
}