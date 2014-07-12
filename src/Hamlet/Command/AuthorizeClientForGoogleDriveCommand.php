<?php

namespace Hamlet\Command;

use Exception;
use Hamlet\GoogleDrive\GoogleDriveClientFactory;
use Hamlet\Profile\ProfileCollection;
use stdClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AuthorizeClientForGoogleDriveCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('google-auth')
            ->setDescription('Authorize Google application profile')
            ->addArgument('profile', InputArgument::REQUIRED, 'Profile name');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $collection = new ProfileCollection();
        $profileName = $input->getArgument('profile');
        $settings = (array) $collection->getProfile($profileName);
        $key = GoogleDriveClientFactory::SETTINGS_KEY;

        if (!isset($settings[$key])) {
            $output->write('<question>Enter Google client ID: </question>');
            $clientId = trim(fgets(STDIN));
            $output->write('<question>Enter Google client secret: </question>');
            $clientSecret = trim(fgets(STDIN));
        } else {
            $clientId = $settings[$key]->clientId;
            $clientSecret = $settings[$key]->clientSecret;
        }

        $factory = new GoogleDriveClientFactory();
        $client = $factory->getClient($clientId, $clientSecret);

        $url = $client->createAuthUrl();
        $output->writeln('Visit the following URL and copy the code');
        $output->writeln($url . PHP_EOL);
        $output->write('<question>Please enter the code: <question>');
        $authCode = trim(fgets(STDIN));

        $collection = new ProfileCollection();
        $profileName = $input->getArgument('profile');
        $settings = (array) $collection->getProfile($profileName);
        $settings[$key] = [
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'accessToken' => json_decode($client->authenticate($authCode)),
        ];
        $collection->setProfile($profileName, $settings);
        $output->writeln("Profile {$profileName} updated");
    }
}