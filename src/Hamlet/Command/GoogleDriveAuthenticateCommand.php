<?php

namespace Hamlet\Command;

use Google_Client;
use Hamlet\GoogleDrive\GoogleDriveClientFactory;
use Hamlet\GoogleDrive\GoogleDriveService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GoogleDriveAuthenticateCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('google-auth')
            ->setDescription('Authenticate application for Google drive')
            ->addArgument('client-id', InputArgument::REQUIRED, 'Client ID')
            ->addArgument('client-secret', InputArgument::REQUIRED, 'Client secret');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $factory = new GoogleDriveClientFactory();
        $client = $factory->getClient($input->getArgument('client-id'), $input->getArgument('client-secret'));

        $url = $client->createAuthUrl();
        print('Visit the following URL' . PHP_EOL);
        print($url . PHP_EOL . PHP_EOL);
        $authCode = trim(fgets(STDIN));
        echo PHP_EOL, $client->authenticate($authCode);
    }
}